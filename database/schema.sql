-- Kids Reward App schema (MySQL 8/9, InnoDB, utf8mb4).
-- Safe to re-run: drops in dependency order, then recreates.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reward_claims;
DROP TABLE IF EXISTS rewards;
DROP TABLE IF EXISTS task_completions;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS currency_changes;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Parents and children share one table, distinguished by `role`.
CREATE TABLE users (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role                 ENUM('parent','child') NOT NULL,
    parent_id            INT UNSIGNED NULL,                 -- which parent owns this child
    username             VARCHAR(50)  NULL,                 -- child login id
    email                VARCHAR(190) NULL,                 -- parent login id
    password_hash        VARCHAR(255) NOT NULL,
    display_name         VARCHAR(100) NOT NULL,
    must_change_password TINYINT(1)   NOT NULL DEFAULT 0,   -- children: 1 until first login
    balance_cents        BIGINT       NOT NULL DEFAULT 0,
    stars                INT          NOT NULL DEFAULT 0,
    currency             CHAR(3)      NULL,                 -- set on parent rows; children inherit
    timezone             VARCHAR(64)  NOT NULL DEFAULT 'UTC', -- IANA tz per user (e.g. America/New_York)
    avatar_emoji         VARCHAR(16)  NULL,
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username),
    KEY idx_users_parent (parent_id),
    CONSTRAINT fk_users_parent FOREIGN KEY (parent_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Audit trail of currency conversions performed by a parent.
CREATE TABLE currency_changes (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id     INT UNSIGNED NOT NULL,
    from_currency CHAR(3)      NULL,
    to_currency   CHAR(3)      NOT NULL,
    exchange_rate DECIMAL(18,8) NOT NULL,                   -- new = old * rate
    changed_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_curchg_parent (parent_id),
    CONSTRAINT fk_curchg_parent FOREIGN KEY (parent_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Money ledger. Withdraw rows start 'pending' until a parent approves.
CREATE TABLE transactions (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id     INT UNSIGNED NOT NULL,
    amount_cents BIGINT       NOT NULL,                     -- + money in, - withdraw
    type         ENUM('reward','withdraw','adjustment','conversion') NOT NULL,
    status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    currency     CHAR(3)      NOT NULL,                     -- currency this row was recorded in
    note         VARCHAR(255) NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at  DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_tx_child (child_id),
    KEY idx_tx_status (status),
    CONSTRAINT fk_tx_child FOREIGN KEY (child_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Task definitions (recurring or one-time).
CREATE TABLE tasks (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id     INT UNSIGNED NOT NULL,
    child_id      INT UNSIGNED NOT NULL,
    title         VARCHAR(150) NOT NULL,
    description   VARCHAR(500) NULL,
    stars         INT          NOT NULL DEFAULT 1,
    frequency     ENUM('once','daily','weekly','monthly') NOT NULL,
    specific_date DATE         NULL,                        -- for 'once'
    weekday       TINYINT      NULL,                        -- 0=Sun..6=Sat for 'weekly'
    day_of_month  TINYINT      NULL,                        -- 1..31 for 'monthly'
    start_date    DATE         NOT NULL,
    end_date      DATE         NULL,
    active        TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tasks_child (child_id),
    KEY idx_tasks_parent (parent_id),
    CONSTRAINT fk_tasks_parent FOREIGN KEY (parent_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_child  FOREIGN KEY (child_id)  REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- One row per (task, due date) the child has acted on.
CREATE TABLE task_completions (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id       INT UNSIGNED NOT NULL,
    child_id      INT UNSIGNED NOT NULL,
    due_date      DATE         NOT NULL,
    status        ENUM('completed','approved','rejected') NOT NULL DEFAULT 'completed',
    stars_awarded INT          NOT NULL DEFAULT 0,
    completed_at  DATETIME     NULL,
    approved_at   DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_completion (task_id, due_date),
    KEY idx_completion_child (child_id),
    KEY idx_completion_status (status),
    CONSTRAINT fk_completion_task  FOREIGN KEY (task_id)  REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_completion_child FOREIGN KEY (child_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Reward catalog. child_id NULL = available to all of the parent's children.
CREATE TABLE rewards (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id   INT UNSIGNED NOT NULL,
    child_id    INT UNSIGNED NULL,
    title       VARCHAR(150) NOT NULL,
    description VARCHAR(500) NULL,
    star_cost   INT          NOT NULL,
    emoji       VARCHAR(16)  NULL,
    active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rewards_parent (parent_id),
    KEY idx_rewards_child (child_id),
    CONSTRAINT fk_rewards_parent FOREIGN KEY (parent_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_rewards_child  FOREIGN KEY (child_id)  REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- A child's claim against a reward; stars are spent at claim time.
CREATE TABLE reward_claims (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reward_id    INT UNSIGNED NOT NULL,
    child_id     INT UNSIGNED NOT NULL,
    star_cost    INT          NOT NULL,                     -- snapshot of cost at claim time
    status       ENUM('pending','completed','rejected') NOT NULL DEFAULT 'pending',
    claimed_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_claims_child (child_id),
    KEY idx_claims_status (status),
    CONSTRAINT fk_claims_reward FOREIGN KEY (reward_id) REFERENCES rewards (id) ON DELETE CASCADE,
    CONSTRAINT fk_claims_child  FOREIGN KEY (child_id)  REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
