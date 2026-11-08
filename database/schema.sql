CREATE DATABASE IF NOT EXISTS vampir_koylu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vampir_koylu;

CREATE TABLE roles (
    id           INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(20)  NOT NULL,
    team         VARCHAR(10)  NOT NULL,
    night_action VARCHAR(10)  NULL,
    description  VARCHAR(255) NOT NULL,
    CONSTRAINT uq_roles_name UNIQUE (name),
    CONSTRAINT ck_roles_team CHECK (team IN ('villagers', 'vampires'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id            INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(40)  NOT NULL,
    email         VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    account_role  VARCHAR(10)  NOT NULL DEFAULT 'player',
    created_at    DATETIME     NOT NULL,
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT ck_users_role CHECK (account_role IN ('player', 'admin'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- vampire_count 0: kurucu seçmedi, oyuncu sayısına göre varsayılan kullanılır
-- version: faz geçişlerinde iyimser kilit
CREATE TABLE games (
    id            INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    room_code     VARCHAR(6)  NOT NULL,
    host_user_id  INT         NOT NULL,
    status        VARCHAR(12) NOT NULL DEFAULT 'lobby',
    vampire_count INT         NOT NULL DEFAULT 0,
    trapper_count INT         NOT NULL DEFAULT 0,
    winner_team   VARCHAR(10) NULL,
    version       INT         NOT NULL DEFAULT 0,
    created_at    DATETIME    NOT NULL,
    CONSTRAINT uq_games_room_code UNIQUE (room_code),
    CONSTRAINT fk_games_host FOREIGN KEY (host_user_id) REFERENCES users (id),
    CONSTRAINT ck_games_status CHECK (status IN ('lobby', 'in_progress', 'finished')),
    CONSTRAINT ck_games_winner CHECK (winner_team IS NULL OR winner_team IN ('villagers', 'vampires'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- güncel faz: en büyük sequence_no
CREATE TABLE phases (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    game_id     INT         NOT NULL,
    sequence_no INT         NOT NULL,
    phase_type  VARCHAR(5)  NOT NULL,
    day_number  INT         NOT NULL,
    started_at  DATETIME    NOT NULL,
    ends_at     DATETIME    NOT NULL,
    CONSTRAINT uq_phases_sequence UNIQUE (game_id, sequence_no),
    CONSTRAINT fk_phases_game FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE,
    CONSTRAINT ck_phases_type CHECK (phase_type IN ('night', 'day', 'vote'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE players (
    id             INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    game_id        INT        NOT NULL,
    user_id        INT        NOT NULL,
    role_id        INT        NULL,
    is_alive       TINYINT(1) NOT NULL DEFAULT 1,
    death_cause    VARCHAR(5) NULL,
    death_phase_id INT        NULL,
    CONSTRAINT uq_players_game_user UNIQUE (game_id, user_id),
    CONSTRAINT fk_players_game FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE,
    CONSTRAINT fk_players_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_players_role FOREIGN KEY (role_id) REFERENCES roles (id),
    CONSTRAINT fk_players_death_phase FOREIGN KEY (death_phase_id) REFERENCES phases (id) ON DELETE SET NULL,
    CONSTRAINT ck_players_death CHECK (death_cause IS NULL OR death_cause IN ('night', 'vote'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- is_cancelled: tuzağa takılan oyuncunun eylemi
CREATE TABLE actions (
    id               INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    phase_id         INT        NOT NULL,
    actor_player_id  INT        NOT NULL,
    target_player_id INT        NOT NULL,
    action_type      VARCHAR(10) NOT NULL,
    is_cancelled     TINYINT(1) NOT NULL DEFAULT 0,
    created_at       DATETIME   NOT NULL,
    CONSTRAINT uq_actions_phase_actor UNIQUE (phase_id, actor_player_id),
    CONSTRAINT fk_actions_phase FOREIGN KEY (phase_id) REFERENCES phases (id) ON DELETE CASCADE,
    CONSTRAINT fk_actions_actor FOREIGN KEY (actor_player_id) REFERENCES players (id) ON DELETE CASCADE,
    CONSTRAINT fk_actions_target FOREIGN KEY (target_player_id) REFERENCES players (id) ON DELETE CASCADE,
    CONSTRAINT ck_actions_type CHECK (action_type IN ('kill', 'protect', 'inspect', 'trap'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- target_player_id NULL: pas
CREATE TABLE votes (
    id               INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    phase_id         INT      NOT NULL,
    voter_player_id  INT      NOT NULL,
    target_player_id INT      NULL,
    created_at       DATETIME NOT NULL,
    CONSTRAINT uq_votes_phase_voter UNIQUE (phase_id, voter_player_id),
    CONSTRAINT fk_votes_phase FOREIGN KEY (phase_id) REFERENCES phases (id) ON DELETE CASCADE,
    CONSTRAINT fk_votes_voter FOREIGN KEY (voter_player_id) REFERENCES players (id) ON DELETE CASCADE,
    CONSTRAINT fk_votes_target FOREIGN KEY (target_player_id) REFERENCES players (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- is_secret: oyun bitene kadar gizli
CREATE TABLE game_logs (
    id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    game_id    INT          NOT NULL,
    event_type VARCHAR(20)  NOT NULL,
    message    VARCHAR(500) NOT NULL,
    is_secret  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at DATETIME     NOT NULL,
    CONSTRAINT fk_logs_game FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- id değerleri src/Models/RoleType.php ile aynı olmalı
INSERT INTO roles (id, name, team, night_action, description) VALUES
    (1, 'villager', 'villagers', NULL,      'Gece yeteneği yoktur. Gündüz oylamada vampirleri bulmaya çalışır.'),
    (2, 'vampire',  'vampires',  'kill',    'Her gece bir kurban seçer. Diğer vampirlerin kim olduğunu bilmez.'),
    (3, 'doctor',   'villagers', 'protect', 'Her gece kendisi dahil bir oyuncuyu korur. Art arda iki gece kendini koruyamaz.'),
    (4, 'seer',     'villagers', 'inspect', 'Her gece bir oyuncunun rolünü öğrenir. Sonucu yalnızca kendisi görür.'),
    (5, 'trapper',  'villagers', 'trap',    'Her gece bir oyuncunun evine tuzak kurar. Eve eylem yapan ilk oyuncunun eylemi iptal olur.');

CREATE INDEX idx_game_logs_game ON game_logs (game_id, id);
CREATE INDEX idx_games_status ON games (status);
CREATE INDEX idx_players_user ON players (user_id);
