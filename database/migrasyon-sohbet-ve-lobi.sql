-- Var olan bir MySQL veritabanını yeni sürüme (sohbet + oyun sonrası lobiye dönüş) taşır.
-- Yalnızca daha önce schema.sql ile kurulmuş veritabanında bir kez çalıştırın:
--   mysql -u root -p vampir_koylu < database/migrasyon-sohbet-ve-lobi.sql
-- Sıfırdan kuruyorsanız gerek yok; schema.sql bunları zaten içerir.
USE vampir_koylu;

ALTER TABLE games ADD COLUMN rematch_code VARCHAR(6) NULL AFTER winner_team;

CREATE TABLE messages (
    id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    game_id    INT          NOT NULL,
    user_id    INT          NOT NULL,
    body       VARCHAR(300) NOT NULL,
    created_at DATETIME     NOT NULL,
    CONSTRAINT fk_messages_game FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_messages_game ON messages (game_id, id);
