<?php
// Работа с таблицей: просмотр, добавление, изменение, удаление записей
require_once 'admin_lib.php';
$tables = db_objects($pdo, 'BASE TABLE');
$t = $_GET['t'] ?? '';
if (!in_array($t, $tables, true)) {            // защита: только существующие таблицы
    http_response_code(404);
    exit('Таблица не найдена');
}
$cols = table_columns($pdo, $t);
$pk = primary_key($cols);
$fks = table_fks($pdo, $t);
$errors = [];

// Значение поля формы -> значение для базы
function form_value(array $c, $v)
{
    if ($v === '' || $v === null) return $c['IS_NULLABLE'] === 'YES' ? null : ($c['COLUMN_DEFAULT'] ?? '');
    if ($c['DATA_TYPE'] === 'datetime') return str_replace('T', ' ', $v);
    if ($c['DATA_TYPE'] === 'decimal') return str_replace([',', ' '], ['.', ''], $v);
    return $v;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM `$t` WHERE `$pk` = ?")->execute([$_POST['id']]);
            flash('Запись удалена.');
            header("Location: table.php?t=$t");
            exit;
        }
        if ($action === 'save') {
            $fields = [];
            $values = [];
            foreach ($cols as $c) {
                $name = $c['COLUMN_NAME'];
                if ($c['EXTRA'] === 'auto_increment') continue;
                if ($name === 'password_hash') {
                    // пароль вводится открытым текстом и сохраняется только в виде хеша
                    $p = $_POST['password'] ?? '';
                    if ($p === '' && !empty($_POST['id'])) continue;
                    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $p)) {
                        $errors[] = 'Пароль: не менее 8 символов, латинские буквы и цифры, одна заглавная';
                        continue;
                    }
                    $fields[] = $name;
                    $values[] = password_hash($p, PASSWORD_DEFAULT);
                    continue;
                }
                $v = $_POST['f'][$name] ?? '';
                if ($v === '' && $c['IS_NULLABLE'] === 'NO' && $c['COLUMN_DEFAULT'] === null) {
                    $errors[] = 'Заполните поле «' . $name . '»';
                }
                if ($name === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Некорректный email';
                }
                $fields[] = $name;
                $values[] = form_value($c, $v);
            }
            if (!$errors) {
                if (!empty($_POST['id'])) {
                    $set = implode(', ', array_map(fn($f) => "`$f` = ?", $fields));
                    $values[] = $_POST['id'];
                    $pdo->prepare("UPDATE `$t` SET $set WHERE `$pk` = ?")->execute($values);
                    flash('Изменения сохранены.');
                } else {
                    $list = implode(', ', array_map(fn($f) => "`$f`", $fields));
                    $marks = implode(', ', array_fill(0, count($fields), '?'));
                    $pdo->prepare("INSERT INTO `$t` ($list) VALUES ($marks)")->execute($values);
                    flash('Запись добавлена.');
                }
                header("Location: table.php?t=$t");
                exit;
            }
        }
    } catch (PDOException $ex) {
        $errors[] = 'Ошибка базы данных: ' . $ex->getMessage();
    }
}

// Режим редактирования
$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM `$t` WHERE `$pk` = ?");
    $st->execute([$_GET['edit']]);
    $edit = $st->fetch();
}
$showForm = $edit || isset($_GET['add']) || $errors;
$rows = $pdo->query("SELECT * FROM `$t` ORDER BY `$pk`")->fetchAll();
$fkOpts = [];
foreach ($fks as $col => [$rt, $rc]) {
    $fkOpts[$col] = fk_options($pdo, $rt, $rc);
}

// Поле формы по типу столбца
function input_for(array $c, $value, array $fkOpts): string
{
    $n = $c['COLUMN_NAME'];
    $name = 'f[' . $n . ']';
    $req = ($c['IS_NULLABLE'] === 'NO' && $c['COLUMN_DEFAULT'] === null) ? ' data-rules="required"' : '';
    if (isset($fkOpts[$n])) {
        $h = '<select name="' . $name . '"' . $req . '>' . ($c['IS_NULLABLE'] === 'YES' ? '<option value="">—</option>' : '');
        foreach ($fkOpts[$n] as $id => $label) {
            $h .= '<option value="' . e($id) . '"' . ((string)$id === (string)$value ? ' selected' : '') . '>' . e($id . ' — ' . $label) . '</option>';
        }
        return $h . '</select>';
    }
    if ($c['DATA_TYPE'] === 'enum') {
        preg_match_all("/'([^']+)'/", $c['COLUMN_TYPE'], $m);
        $h = '<select name="' . $name . '">';
        foreach ($m[1] as $o) $h .= '<option' . ($o === $value ? ' selected' : '') . '>' . e($o) . '</option>';
        return $h . '</select>';
    }
    if ($c['COLUMN_TYPE'] === 'tinyint(1)') {
        return '<select name="' . $name . '"><option value="0"' . ($value == 0 ? ' selected' : '') . '>0 — нет</option><option value="1"' . ($value == 1 ? ' selected' : '') . '>1 — да</option></select>';
    }
    if ($c['DATA_TYPE'] === 'text') return '<textarea name="' . $name . '"' . $req . '>' . e($value) . '</textarea>';
    $type = match ($c['DATA_TYPE']) {
        'date' => 'date',
        'datetime' => 'datetime-local',
        'int' => 'number',
        default => 'text',
    };
    if ($type === 'datetime-local' && $value) $value = str_replace(' ', 'T', substr($value, 0, 16));
    $extra = $c['DATA_TYPE'] === 'decimal' ? ' data-mask="money" inputmode="decimal"' : '';
    if ($n === 'email') $req = ' data-rules="required email"';
    if ($n === 'phone') $extra = ' data-mask="phone" data-rules="phone" placeholder="+7 (___) ___-__-__"';
    return '<input type="' . $type . '" name="' . $name . '" value="' . e($value) . '"' . $req . $extra . '>';
}

$title = TABLE_TITLES[$t] ?? $t;
$page = 'admin';
require '../includes/header.php';
?>
<div class="admin">
    <?php admin_menu($pdo, $t); ?>
    <div>
        <div class="section-title">
            <h1><?= e($title) ?> <span class="muted" style="font-size:18px">(<?= e($t) ?>)</span></h1>
            <a class="btn btn-primary" href="table.php?t=<?= e($t) ?>&add=1">+ Добавить запись</a>
        </div>
        <?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>

        <?php if ($showForm): ?>
            <div class="card" style="margin-bottom:18px">
                <h3><?= $edit ? 'Изменение записи № ' . e($edit[$pk]) : 'Новая запись' ?></h3>
                <form method="post" data-validate novalidate>
                    <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($edit[$pk] ?? '') ?>">
                    <div class="form-row">
                        <?php foreach ($cols as $c):
                            if ($c['EXTRA'] === 'auto_increment') continue; ?>
                            <?php if ($c['COLUMN_NAME'] === 'password_hash'): ?>
                                <div class="field">Пароль <?= $edit ? '(оставьте пустым, чтобы не менять)' : '*' ?>
                                    <input type="password" name="password" <?= $edit ? '' : 'data-rules="required password"' ?>></div>
                            <?php else: ?>
                                <div class="field"><?= e($c['COLUMN_NAME']) ?> <span class="muted" style="font-weight:400">(<?= e($c['COLUMN_TYPE']) ?>)</span>
                                    <?= input_for($c, $edit[$c['COLUMN_NAME']] ?? ($_POST['f'][$c['COLUMN_NAME']] ?? ''), $fkOpts) ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-primary" type="submit">Сохранить</button>
                    <a class="btn btn-ghost" href="table.php?t=<?= e($t) ?>">Отмена</a>
                </form>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="table-wrap">
                <table class="t">
                    <tr><?php foreach ($cols as $c): ?><th><?= e($c['COLUMN_NAME']) ?></th><?php endforeach; ?><th class="num">Действия</th></tr>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <?php foreach ($cols as $c):
                                $val = $r[$c['COLUMN_NAME']];
                                if ($c['COLUMN_NAME'] === 'password_hash') $val = mb_substr($val, 0, 12) . '…';
                                elseif (is_string($val) && mb_strlen($val) > 60) $val = mb_substr($val, 0, 60) . '…'; ?>
                                <td><?= e($val) ?></td>
                            <?php endforeach; ?>
                            <td class="num" style="white-space:nowrap">
                                <a class="btn btn-ghost btn-sm" href="table.php?t=<?= e($t) ?>&edit=<?= e($r[$pk]) ?>">Изменить</a>
                                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($r[$pk]) ?>">
                                    <button class="btn btn-danger btn-sm" data-confirm="Удалить запись № <?= e($r[$pk]) ?>?">Удалить</button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require '../includes/footer.php'; ?>
