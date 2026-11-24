-- Schema for the Gather event app. Safe to run repeatedly.

CREATE TABLE IF NOT EXISTS users (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       BIGINT UNSIGNED NOT NULL,
    slug          VARCHAR(190) NOT NULL UNIQUE,
    title         VARCHAR(160) NOT NULL,
    summary       VARCHAR(255) NULL,
    description   MEDIUMTEXT NULL,
    cover_image   VARCHAR(255) NULL,
    venue_name    VARCHAR(160) NULL,
    venue_address VARCHAR(255) NULL,
    timezone      VARCHAR(64) NOT NULL DEFAULT 'UTC',
    starts_at     DATETIME NOT NULL,
    ends_at       DATETIME NULL,
    capacity      INT UNSIGNED NULL,
    status        ENUM('draft','published','cancelled') NOT NULL DEFAULT 'draft',
    published_at  DATETIME NULL,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL,
    CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_events_feed (status, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registrations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id        BIGINT UNSIGNED NOT NULL,
    reference       CHAR(12) NOT NULL UNIQUE,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    phone           VARCHAR(32) NULL,
    status          ENUM('confirmed','waitlisted','cancelled') NOT NULL DEFAULT 'confirmed',
    checked_in_at   DATETIME NULL,
    cancelled_at    DATETIME NULL,
    reminder_sent_at DATETIME NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY uq_reg_event_email (event_id, email),
    KEY idx_reg_list (event_id, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    email       VARCHAR(190) PRIMARY KEY,
    token_hash  CHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    bucket      VARCHAR(191) PRIMARY KEY,
    hits        INT UNSIGNED NOT NULL DEFAULT 0,
    resets_at   DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
