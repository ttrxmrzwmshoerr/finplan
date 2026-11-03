-- ------------------------------------------------------------
-- База данных веб-приложения «ФинПлан» – сервис для планирования финансов
-- СУБД: MySQL / MariaDB (XAMPP), кодировка utf8mb4
-- ------------------------------------------------------------
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Роли пользователей
CREATE TABLE roles (
  id_role    INT AUTO_INCREMENT PRIMARY KEY,
  role_name  VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Пользователи
CREATE TABLE users (
  id_user        INT AUTO_INCREMENT PRIMARY KEY,
  id_role        INT NOT NULL DEFAULT 2,
  user_name      VARCHAR(60) NOT NULL,
  email          VARCHAR(100) NOT NULL UNIQUE,
  phone          VARCHAR(20) NULL,
  password_hash  VARCHAR(255) NOT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_roles FOREIGN KEY (id_role) REFERENCES roles(id_role)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Валюты и курсы к рублю
CREATE TABLE currencies (
  id_currency    INT AUTO_INCREMENT PRIMARY KEY,
  currency_code  CHAR(3) NOT NULL UNIQUE,
  currency_name  VARCHAR(50) NOT NULL,
  currency_symbol VARCHAR(5) NOT NULL,
  rate_to_rub    DECIMAL(12,4) NOT NULL DEFAULT 1.0000,
  rate_date      DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Типы счетов
CREATE TABLE account_types (
  id_account_type INT AUTO_INCREMENT PRIMARY KEY,
  type_name       VARCHAR(40) NOT NULL UNIQUE,
  is_savings      TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Счета пользователей
CREATE TABLE accounts (
  id_account      INT AUTO_INCREMENT PRIMARY KEY,
  id_user         INT NOT NULL,
  id_account_type INT NOT NULL,
  id_currency     INT NOT NULL,
  account_name    VARCHAR(60) NOT NULL,
  balance         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_accounts_users FOREIGN KEY (id_user) REFERENCES users(id_user)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_accounts_types FOREIGN KEY (id_account_type) REFERENCES account_types(id_account_type)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_accounts_currencies FOREIGN KEY (id_currency) REFERENCES currencies(id_currency)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Категории доходов и расходов
CREATE TABLE categories (
  id_category    INT AUTO_INCREMENT PRIMARY KEY,
  category_name  VARCHAR(50) NOT NULL,
  category_type  ENUM('income','expense') NOT NULL,
  color          CHAR(7) NOT NULL DEFAULT '#25315C'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Операции (доходы и расходы)
CREATE TABLE transactions (
  id_transaction   INT AUTO_INCREMENT PRIMARY KEY,
  id_account       INT NOT NULL,
  id_category      INT NOT NULL,
  amount           DECIMAL(12,2) NOT NULL,
  transaction_date DATE NOT NULL,
  comment          VARCHAR(255) NULL,
  CONSTRAINT fk_transactions_accounts FOREIGN KEY (id_account) REFERENCES accounts(id_account)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_transactions_categories FOREIGN KEY (id_category) REFERENCES categories(id_category)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. Бюджеты (лимит расходов по категории на месяц)
CREATE TABLE budgets (
  id_budget     INT AUTO_INCREMENT PRIMARY KEY,
  id_user       INT NOT NULL,
  id_category   INT NOT NULL,
  budget_month  DATE NOT NULL,
  limit_amount  DECIMAL(12,2) NOT NULL,
  UNIQUE KEY uq_budget (id_user, id_category, budget_month),
  CONSTRAINT fk_budgets_users FOREIGN KEY (id_user) REFERENCES users(id_user)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_budgets_categories FOREIGN KEY (id_category) REFERENCES categories(id_category)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. Финансовые цели
CREATE TABLE goals (
  id_goal         INT AUTO_INCREMENT PRIMARY KEY,
  id_user         INT NOT NULL,
  goal_name       VARCHAR(80) NOT NULL,
  target_amount   DECIMAL(12,2) NOT NULL,
  current_amount  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  deadline        DATE NOT NULL,
  goal_status     ENUM('active','done') NOT NULL DEFAULT 'active',
  CONSTRAINT fk_goals_users FOREIGN KEY (id_user) REFERENCES users(id_user)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 10. Советы по финансовой грамотности
CREATE TABLE tips (
  id_tip       INT AUTO_INCREMENT PRIMARY KEY,
  id_user      INT NOT NULL,
  id_category  INT NULL,
  tip_title    VARCHAR(120) NOT NULL,
  tip_text     TEXT NOT NULL,
  published_at DATE NOT NULL,
  CONSTRAINT fk_tips_users FOREIGN KEY (id_user) REFERENCES users(id_user)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_tips_categories FOREIGN KEY (id_category) REFERENCES categories(id_category)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Заполнение таблиц
-- ------------------------------------------------------------
INSERT INTO roles (id_role, role_name) VALUES
 (1,'Администратор'),(2,'Пользователь');

-- пароли: Admin2026, Anna2026, Igor2026, Maria2026
INSERT INTO users (id_user, id_role, user_name, email, phone, password_hash, created_at) VALUES
 (1,1,'Алина Гарипова','admin@finplan.ru','+7 (900) 111-22-33','$2y$10$pouFbuCy4G.fILk3OPcjRu/yRjlP00yjxij6AVwD9c5kvk8zcSdau','2026-09-01 10:00:00'),
 (2,2,'Анна Смирнова','anna@mail.ru','+7 (912) 345-67-89','$2y$10$ZHap/5sYwaZXNJ.Z8p6ROuPwoye4.h72JYprLjByWoarZFe8cwGMi','2026-09-12 18:24:00'),
 (3,2,'Игорь Петров','i.petrov@yandex.ru','+7 (926) 555-12-34','$2y$10$rYol18nQFCMHTsIaWqveDuq2I3U2avIIyyYgmwDnPz.pAYXhZajZy','2026-09-15 09:10:00'),
 (4,2,'Мария Ким','kim.m@gmail.com','+7 (903) 777-88-99','$2y$10$2xlzCMlu2kIPulYdIHbSpOrFC0SW5RB3gUtt7AVGrKY3TnvY0eKhC','2026-10-02 20:45:00');

INSERT INTO currencies (id_currency, currency_code, currency_name, currency_symbol, rate_to_rub, rate_date) VALUES
 (1,'RUB','Российский рубль','₽',1.0000,'2026-11-03'),
 (2,'USD','Доллар США','$',80.9734,'2026-11-03'),
 (3,'EUR','Евро','€',93.4120,'2026-11-03'),
 (4,'CNY','Китайский юань','¥',11.3510,'2026-11-03'),
 (5,'KZT','Казахстанский тенге','₸',0.1592,'2026-11-03'),
 (6,'TRY','Турецкая лира','₺',1.9210,'2026-11-03');

INSERT INTO account_types (id_account_type, type_name, is_savings) VALUES
 (1,'Банковская карта',0),(2,'Наличные',0),(3,'Сберегательный счёт',1),(4,'Вклад',1);

INSERT INTO accounts (id_account, id_user, id_account_type, id_currency, account_name, balance) VALUES
 (1,2,1,1,'Карта Сбер',68340.00),
 (2,2,1,1,'Карта Т-Банк',12640.00),
 (3,2,2,1,'Наличные',3600.00),
 (4,2,3,1,'Накопительный счёт «Отпуск»',40000.00),
 (5,2,4,2,'Валютный вклад',1500.00),
 (6,3,1,1,'Карта Альфа-Банк',25400.00),
 (7,3,3,1,'Сберегательный счёт «Резерв»',120000.00),
 (8,4,1,1,'Карта ВТБ',18900.00);

INSERT INTO categories (id_category, category_name, category_type, color) VALUES
 (1,'Зарплата','income','#2FB89A'),
 (2,'Подработка','income','#20C997'),
 (3,'Продукты','expense','#FF6A4D'),
 (4,'Жильё и ЖКХ','expense','#25315C'),
 (5,'Транспорт','expense','#B9B7F2'),
 (6,'Кафе и рестораны','expense','#FFC65C'),
 (7,'Здоровье','expense','#2FB89A'),
 (8,'Развлечения','expense','#8C93B0');

INSERT INTO transactions (id_account, id_category, amount, transaction_date, comment) VALUES
 (1,1,85000.00,'2026-11-01','Зарплата за октябрь'),
 (1,4,35000.00,'2026-11-01','Аренда квартиры'),
 (2,3,2340.00,'2026-11-03','Пятёрочка'),
 (2,5,560.00,'2026-11-03','Пополнение «Тройки»'),
 (3,6,420.00,'2026-11-02','Кофейня у дома'),
 (2,3,1890.00,'2026-11-02','Перекрёсток'),
 (1,8,900.00,'2026-10-31','Кино, 2 билета'),
 (1,7,1240.00,'2026-10-30','Аптека'),
 (6,1,64000.00,'2026-11-01','Зарплата'),
 (6,3,3150.00,'2026-11-02','ВкусВилл');

INSERT INTO budgets (id_user, id_category, budget_month, limit_amount) VALUES
 (2,3,'2026-11-01',18000.00),
 (2,4,'2026-11-01',35000.00),
 (2,5,'2026-11-01',4000.00),
 (2,6,'2026-11-01',4000.00),
 (2,8,'2026-11-01',5000.00),
 (3,3,'2026-11-01',15000.00),
 (2,3,'2026-10-01',17000.00);

INSERT INTO goals (id_user, goal_name, target_amount, current_amount, deadline, goal_status) VALUES
 (2,'Отпуск в августе',100000.00,68000.00,'2027-08-01','active'),
 (2,'Новый ноутбук',80000.00,28000.00,'2027-04-01','active'),
 (2,'Подушка безопасности',200000.00,104000.00,'2027-12-31','active'),
 (3,'Автомобиль',600000.00,120000.00,'2028-06-01','active'),
 (4,'Курсы английского',45000.00,45000.00,'2026-10-15','done');

INSERT INTO tips (id_user, id_category, tip_title, tip_text, published_at) VALUES
 (1,NULL,'Бюджет 50/30/20: с чего начать','Делите доход на три части: 50 % – обязательные расходы, 30 % – желания, 20 % – накопления и цели. Задайте лимиты в разделе «Бюджет» личного кабинета.','2026-11-02'),
 (1,NULL,'Финансовая подушка: сколько нужно','Подушка безопасности – это запас на 3–6 месяцев обязательных расходов. Держите её на отдельном сберегательном счёте.','2026-10-28'),
 (1,3,'Покупки без импульса','Составляйте список продуктов заранее и не ходите в магазин голодным – это экономит до 15 % бюджета на продукты.','2026-10-21'),
 (1,6,'Кофе с собой – тоже расход','Ежедневный кофе по 250 ₽ – это около 7 500 ₽ в месяц. Отмечайте такие траты в категории «Кафе и рестораны».','2026-10-14'),
 (1,5,'Проездной выгоднее разовых поездок','Если вы ездите на работу каждый день, посчитайте стоимость месячного проездного – обычно он окупается за две недели.','2026-10-07');

-- ------------------------------------------------------------
-- Представления (запросы из «Тем УП»)
-- ------------------------------------------------------------

-- 1. Список всех валют с курсом
CREATE OR REPLACE VIEW v_currency_rates AS
SELECT c.currency_code   AS code,
       c.currency_name   AS currency,
       c.currency_symbol AS symbol,
       c.rate_to_rub     AS rate_to_rub,
       c.rate_date       AS rate_date
FROM currencies c
ORDER BY c.currency_code;

-- 2. Бюджет пользователя на текущий месяц (лимит, потрачено, остаток)
CREATE OR REPLACE VIEW v_budget_current_month AS
SELECT b.id_user,
       u.user_name,
       cat.category_name,
       b.limit_amount,
       COALESCE(SUM(t.amount), 0)                  AS spent,
       b.limit_amount - COALESCE(SUM(t.amount), 0) AS remaining
FROM budgets b
JOIN users u        ON u.id_user = b.id_user
JOIN categories cat ON cat.id_category = b.id_category
LEFT JOIN accounts a     ON a.id_user = b.id_user
LEFT JOIN transactions t ON t.id_account = a.id_account
                         AND t.id_category = b.id_category
                         AND t.transaction_date >= b.budget_month
                         AND t.transaction_date <  b.budget_month + INTERVAL 1 MONTH
WHERE b.budget_month = DATE_FORMAT(CURDATE(), '%Y-%m-01')
GROUP BY b.id_budget, b.id_user, u.user_name, cat.category_name, b.limit_amount;

-- 3. Финансовые цели пользователя
CREATE OR REPLACE VIEW v_user_goals AS
SELECT g.id_goal,
       g.id_user,
       u.user_name,
       g.goal_name,
       g.target_amount,
       g.current_amount,
       ROUND(g.current_amount / g.target_amount * 100, 1) AS progress_percent,
       g.deadline,
       g.goal_status
FROM goals g
JOIN users u ON u.id_user = g.id_user;

-- 4. Названия сберегательных счетов пользователя
CREATE OR REPLACE VIEW v_savings_accounts AS
SELECT a.id_user,
       u.user_name,
       a.account_name,
       atp.type_name,
       a.balance,
       c.currency_code
FROM accounts a
JOIN users u          ON u.id_user = a.id_user
JOIN account_types atp ON atp.id_account_type = a.id_account_type
JOIN currencies c     ON c.id_currency = a.id_currency
WHERE atp.is_savings = 1;

-- 5. Расходы пользователя по категориям за текущий месяц
CREATE OR REPLACE VIEW v_expenses_by_category_month AS
SELECT a.id_user,
       u.user_name,
       cat.category_name,
       DATE_FORMAT(t.transaction_date, '%Y-%m') AS month,
       SUM(t.amount)  AS total_spent,
       COUNT(*)       AS operations
FROM transactions t
JOIN accounts a     ON a.id_account = t.id_account
JOIN users u        ON u.id_user = a.id_user
JOIN categories cat ON cat.id_category = t.id_category
WHERE cat.category_type = 'expense'
  AND t.transaction_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
GROUP BY a.id_user, u.user_name, cat.category_name, DATE_FORMAT(t.transaction_date, '%Y-%m');
