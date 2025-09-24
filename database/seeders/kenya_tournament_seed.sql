-- Kenya Pool Tournament Database Seed
-- This script sets up initial data for regions, counties, communities, users, and tournaments
-- Formatted for MySQL Workbench execution

-- Drop and recreate the database
DROP DATABASE IF EXISTS poolapp;
CREATE DATABASE poolapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE poolapp;

-- Create all required tables first
CREATE TABLE regions (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE counties (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    region_id bigint unsigned NOT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY counties_region_id_foreign (region_id),
    CONSTRAINT counties_region_id_foreign FOREIGN KEY (region_id) REFERENCES regions (id)
);

CREATE TABLE communities (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    county_id bigint unsigned NOT NULL,
    region_id bigint unsigned NOT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY communities_county_id_foreign (county_id),
    KEY communities_region_id_foreign (region_id),
    CONSTRAINT communities_county_id_foreign FOREIGN KEY (county_id) REFERENCES counties (id),
    CONSTRAINT communities_region_id_foreign FOREIGN KEY (region_id) REFERENCES regions (id)
);

CREATE TABLE users (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    username varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    email_verified_at timestamp NULL DEFAULT NULL,
    password varchar(255) NOT NULL,
    community_id bigint unsigned DEFAULT NULL,
    county_id bigint unsigned DEFAULT NULL,
    region_id bigint unsigned DEFAULT NULL,
    preferred_days json DEFAULT NULL,
    points int DEFAULT 0,
    level enum('community','county','regional','national') DEFAULT 'community',
    remember_token varchar(100) DEFAULT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_username_unique (username),
    UNIQUE KEY users_email_unique (email),
    KEY users_community_id_foreign (community_id),
    KEY users_county_id_foreign (county_id),
    KEY users_region_id_foreign (region_id),
    CONSTRAINT users_community_id_foreign FOREIGN KEY (community_id) REFERENCES communities (id),
    CONSTRAINT users_county_id_foreign FOREIGN KEY (county_id) REFERENCES counties (id),
    CONSTRAINT users_region_id_foreign FOREIGN KEY (region_id) REFERENCES regions (id)
);

CREATE TABLE tournaments (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    special tinyint(1) NOT NULL DEFAULT 0,
    community_prize decimal(10,2) DEFAULT NULL,
    county_prize decimal(10,2) DEFAULT NULL,
    regional_prize decimal(10,2) DEFAULT NULL,
    national_prize decimal(10,2) DEFAULT NULL,
    tournament_charge decimal(8,2) DEFAULT NULL,
    status enum('upcoming','active','paused','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    automation_mode enum('manual','automatic') NOT NULL DEFAULT 'manual',
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE registered_users (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    player_id bigint unsigned NOT NULL,
    tournament_id bigint unsigned NOT NULL,
    status enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    payment_status enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    payment_id varchar(255) DEFAULT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY registered_users_player_tournament_unique (player_id,tournament_id),
    KEY registered_users_tournament_id_foreign (tournament_id),
    CONSTRAINT registered_users_player_id_foreign FOREIGN KEY (player_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT registered_users_tournament_id_foreign FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE
);

CREATE TABLE matches (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    match_name varchar(255) DEFAULT NULL,
    player_1_id bigint unsigned DEFAULT NULL,
    player_2_id bigint unsigned DEFAULT NULL,
    player_1_points int DEFAULT NULL,
    player_2_points int DEFAULT NULL,
    winner_id bigint unsigned DEFAULT NULL,
    bye_player_id bigint unsigned DEFAULT NULL,
    level enum('community','county','regional','national','special') NOT NULL,
    round_name varchar(255) NOT NULL,
    tournament_id bigint unsigned NOT NULL,
    status enum('pending','scheduled','in_progress','pending_confirmation','completed','forfeit') NOT NULL DEFAULT 'pending',
    group_id bigint unsigned DEFAULT NULL,
    proposed_dates json DEFAULT NULL,
    player_1_preferred_dates json DEFAULT NULL,
    player_2_preferred_dates json DEFAULT NULL,
    scheduled_date datetime DEFAULT NULL,
    submitted_by bigint unsigned DEFAULT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY matches_player_1_id_foreign (player_1_id),
    KEY matches_player_2_id_foreign (player_2_id),
    KEY matches_winner_id_foreign (winner_id),
    KEY matches_bye_player_id_foreign (bye_player_id),
    KEY matches_tournament_id_foreign (tournament_id),
    KEY matches_submitted_by_foreign (submitted_by),
    KEY idx_tournament_level_round_status (tournament_id,level,round_name,status),
    KEY matches_group_id_index (group_id),
    CONSTRAINT matches_bye_player_id_foreign FOREIGN KEY (bye_player_id) REFERENCES users (id),
    CONSTRAINT matches_player_1_id_foreign FOREIGN KEY (player_1_id) REFERENCES users (id),
    CONSTRAINT matches_player_2_id_foreign FOREIGN KEY (player_2_id) REFERENCES users (id),
    CONSTRAINT matches_submitted_by_foreign FOREIGN KEY (submitted_by) REFERENCES users (id),
    CONSTRAINT matches_tournament_id_foreign FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE,
    CONSTRAINT matches_winner_id_foreign FOREIGN KEY (winner_id) REFERENCES users (id)
);

CREATE TABLE notifications (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    user_id bigint unsigned NOT NULL,
    title varchar(255) NOT NULL,
    message text NOT NULL,
    type enum('info','success','warning','error') NOT NULL DEFAULT 'info',
    read_at timestamp NULL DEFAULT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY notifications_user_id_foreign (user_id),
    CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE chat_messages (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    sender_id bigint unsigned NOT NULL,
    receiver_id bigint unsigned NOT NULL,
    message text NOT NULL,
    read_at timestamp NULL DEFAULT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY chat_messages_sender_id_foreign (sender_id),
    KEY chat_messages_receiver_id_foreign (receiver_id),
    CONSTRAINT chat_messages_receiver_id_foreign FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT chat_messages_sender_id_foreign FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE winners (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    player_id bigint unsigned NOT NULL,
    position tinyint unsigned NOT NULL,
    level enum('community','county','regional','national','special') NOT NULL,
    level_id bigint unsigned DEFAULT NULL,
    tournament_id bigint unsigned NOT NULL,
    prize_awarded tinyint(1) NOT NULL DEFAULT 0,
    prize_amount decimal(10,2) DEFAULT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY winners_player_id_foreign (player_id),
    KEY winners_tournament_id_foreign (tournament_id),
    KEY idx_tournament_level_position (tournament_id,level,position),
    CONSTRAINT winners_player_id_foreign FOREIGN KEY (player_id) REFERENCES users (id),
    CONSTRAINT winners_tournament_id_foreign FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE
);

-- Data population starts here
-- Note: Tables are already created above, no need to truncate since we dropped the database

-- =====================================================
-- REGIONS (7 Kenyan Regions)
-- =====================================================
INSERT INTO regions (id, name, created_at, updated_at) VALUES
(1, 'Nairobi', NOW(), NOW()),
(2, 'Central', NOW(), NOW()),
(3, 'Coast', NOW(), NOW()),
(4, 'Eastern', NOW(), NOW()),
(5, 'North Eastern', NOW(), NOW()),
(6, 'Nyanza', NOW(), NOW()),
(7, 'Rift Valley', NOW(), NOW()),
(8, 'Western', NOW(), NOW());

-- =====================================================
-- COUNTIES (47 Kenyan Counties)
-- =====================================================
INSERT INTO counties (id, name, region_id, created_at, updated_at) VALUES
-- Nairobi Region
(1, 'Nairobi', 1, NOW(), NOW()),

-- Central Region
(2, 'Kiambu', 2, NOW(), NOW()),
(3, 'Murang\'a', 2, NOW(), NOW()),
(4, 'Nyeri', 2, NOW(), NOW()),
(5, 'Kirinyaga', 2, NOW(), NOW()),
(6, 'Nyandarua', 2, NOW(), NOW()),

-- Coast Region
(7, 'Mombasa', 3, NOW(), NOW()),
(8, 'Kwale', 3, NOW(), NOW()),
(9, 'Kilifi', 3, NOW(), NOW()),
(10, 'Tana River', 3, NOW(), NOW()),
(11, 'Lamu', 3, NOW(), NOW()),
(12, 'Taita Taveta', 3, NOW(), NOW()),

-- Eastern Region
(13, 'Marsabit', 4, NOW(), NOW()),
(14, 'Isiolo', 4, NOW(), NOW()),
(15, 'Meru', 4, NOW(), NOW()),
(16, 'Tharaka-Nithi', 4, NOW(), NOW()),
(17, 'Embu', 4, NOW(), NOW()),
(18, 'Kitui', 4, NOW(), NOW()),
(19, 'Machakos', 4, NOW(), NOW()),
(20, 'Makueni', 4, NOW(), NOW()),

-- North Eastern Region
(21, 'Garissa', 5, NOW(), NOW()),
(22, 'Wajir', 5, NOW(), NOW()),
(23, 'Mandera', 5, NOW(), NOW()),

-- Nyanza Region
(24, 'Siaya', 6, NOW(), NOW()),
(25, 'Kisumu', 6, NOW(), NOW()),
(26, 'Homa Bay', 6, NOW(), NOW()),
(27, 'Migori', 6, NOW(), NOW()),
(28, 'Kisii', 6, NOW(), NOW()),
(29, 'Nyamira', 6, NOW(), NOW()),

-- Rift Valley Region
(30, 'Turkana', 7, NOW(), NOW()),
(31, 'West Pokot', 7, NOW(), NOW()),
(32, 'Samburu', 7, NOW(), NOW()),
(33, 'Trans-Nzoia', 7, NOW(), NOW()),
(34, 'Uasin Gishu', 7, NOW(), NOW()),
(35, 'Elgeyo-Marakwet', 7, NOW(), NOW()),
(36, 'Nandi', 7, NOW(), NOW()),
(37, 'Baringo', 7, NOW(), NOW()),
(38, 'Laikipia', 7, NOW(), NOW()),
(39, 'Nakuru', 7, NOW(), NOW()),
(40, 'Narok', 7, NOW(), NOW()),
(41, 'Kajiado', 7, NOW(), NOW()),
(42, 'Kericho', 7, NOW(), NOW()),
(43, 'Bomet', 7, NOW(), NOW()),

-- Western Region
(44, 'Kakamega', 8, NOW(), NOW()),
(45, 'Vihiga', 8, NOW(), NOW()),
(46, 'Bungoma', 8, NOW(), NOW()),
(47, 'Busia', 8, NOW(), NOW());

-- =====================================================
-- COMMUNITIES (Variable per county, 1-10 communities)
-- =====================================================
SET @community_id = 0;

-- Delimiter for stored procedure
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS InsertCommunities()
BEGIN
    DECLARE county_counter INT DEFAULT 1;
    DECLARE community_counter INT;
    DECLARE num_communities INT;
    DECLARE community_id_counter INT DEFAULT 1;
    
    WHILE county_counter <= 47 DO
        -- Random number of communities per county (1-10)
        SET num_communities = FLOOR(1 + RAND() * 10);
        SET community_counter = 1;
        
        WHILE community_counter <= num_communities DO
            INSERT INTO communities (id, name, county_id, region_id, created_at, updated_at)
            SELECT 
                community_id_counter,
                CONCAT(
                    (SELECT name FROM counties WHERE id = county_counter),
                    ' Community ',
                    community_counter
                ),
                county_counter,
                (SELECT region_id FROM counties WHERE id = county_counter),
                NOW(),
                NOW();
            
            SET community_counter = community_counter + 1;
            SET community_id_counter = community_id_counter + 1;
        END WHILE;
        
        SET county_counter = county_counter + 1;
    END WHILE;
END$$

DELIMITER ;

CALL InsertCommunities();
DROP PROCEDURE InsertCommunities;

-- =====================================================
-- USERS (12-20 players per community)
-- =====================================================
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS InsertUsers()
BEGIN
    DECLARE community_counter INT;
    DECLARE max_community INT;
    DECLARE user_counter INT;
    DECLARE num_users INT;
    DECLARE user_id_counter INT DEFAULT 1;
    DECLARE username_base VARCHAR(50);
    
    SELECT MAX(id) INTO max_community FROM communities;
    SET community_counter = 1;
    
    WHILE community_counter <= max_community DO
        -- Random number of users per community (12-20)
        SET num_users = FLOOR(12 + RAND() * 9);
        SET user_counter = 1;
        
        WHILE user_counter <= num_users DO
            -- Generate unique username
            SET username_base = CONCAT('player_c', community_counter, '_u', user_counter);
            
            INSERT INTO users (
                id, name, username, email, password, 
                community_id, county_id, region_id,
                preferred_days, points, level,
                email_verified_at, created_at, updated_at
            )
            SELECT 
                user_id_counter,
                CONCAT('Player ', user_id_counter),
                username_base,
                CONCAT(username_base, '@poolapp.com'),
                '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "password"
                community_counter,
                (SELECT county_id FROM communities WHERE id = community_counter),
                (SELECT region_id FROM communities WHERE id = community_counter),
                JSON_ARRAY('Monday', 'Wednesday', 'Friday', 'Saturday', 'Sunday'),
                FLOOR(500 + RAND() * 2000), -- Random points between 500-2500
                CASE 
                    WHEN RAND() < 0.1 THEN 'national'
                    WHEN RAND() < 0.3 THEN 'regional'
                    WHEN RAND() < 0.6 THEN 'county'
                    ELSE 'community'
                END,
                NOW(),
                NOW(),
                NOW()
            FROM communities 
            WHERE id = community_counter
            LIMIT 1;
            
            SET user_counter = user_counter + 1;
            SET user_id_counter = user_id_counter + 1;
        END WHILE;
        
        SET community_counter = community_counter + 1;
    END WHILE;
    
    -- Add an admin user
    INSERT INTO users (
        id, name, username, email, password,
        community_id, county_id, region_id,
        preferred_days, points, level,
        email_verified_at, created_at, updated_at
    ) VALUES (
        user_id_counter,
        'Admin User',
        'admin',
        'admin@cuesports.com',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "password"
        1, 1, 1,
        JSON_ARRAY('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
        5000,
        'national',
        NOW(), NOW(), NOW()
    );
END$$

DELIMITER ;

CALL InsertUsers();
DROP PROCEDURE InsertUsers;

-- =====================================================
-- TOURNAMENTS
-- =====================================================
INSERT INTO tournaments (
    id, name, special, community_prize, county_prize, 
    regional_prize, national_prize, tournament_charge,
    status, automation_mode, created_at, updated_at
) VALUES
(
    1,
    'Kenya National Championship 2024',
    0, -- Not special (hierarchical)
    50000, -- Community prize in KES
    100000, -- County prize
    250000, -- Regional prize
    500000, -- National prize
    500, -- Registration fee
    'upcoming',
    'manual',
    NOW(),
    NOW()
),
(
    2,
    'Kenya Pool Masters Special Tournament',
    1, -- Special tournament (flat structure)
    0, -- No community prize for special
    0, -- No county prize for special
    0, -- No regional prize for special
    1000000, -- Grand prize for special tournament
    1000, -- Higher registration fee
    'upcoming',
    'automatic',
    NOW(),
    NOW()
);

-- =====================================================
-- TOURNAMENT REGISTRATIONS
-- Register a diverse set of players for both tournaments
-- =====================================================

-- Register players for National Championship (Tournament 1)
-- Select random players from different regions and communities
INSERT INTO registered_users (player_id, tournament_id, status, payment_status, payment_id, created_at, updated_at)
SELECT 
    u.id,
    1,
    'approved',
    CASE 
        WHEN RAND() < 0.1 THEN 'pending'
        ELSE 'paid'
    END,
    CONCAT('pi_', MD5(CONCAT(u.id, '_tournament_1'))),
    NOW(),
    NOW()
FROM users u
WHERE u.email != 'admin@cuesports.com'
ORDER BY RAND()
LIMIT 800;

-- Register players for Special Tournament (Tournament 2)
-- Select top players based on points
INSERT INTO registered_users (player_id, tournament_id, status, payment_status, payment_id, created_at, updated_at)
SELECT 
    u.id,
    2,
    'approved',
    'paid',
    CONCAT('pi_', MD5(CONCAT(u.id, '_tournament_2'))),
    NOW(),
    NOW()
FROM users u
WHERE u.email != 'admin@cuesports.com'
  AND u.points >= 1500 -- Only higher-rated players for special tournament
  AND NOT EXISTS (
      SELECT 1 FROM registered_users ru 
      WHERE ru.player_id = u.id AND ru.tournament_id = 2
  )
ORDER BY u.points DESC
LIMIT 200;

-- =====================================================
-- STATISTICS VIEW
-- =====================================================
SELECT 'Database Seed Complete!' as Status;

SELECT 'Regions Created:' as Metric, COUNT(*) as Count FROM regions
UNION ALL
SELECT 'Counties Created:', COUNT(*) FROM counties
UNION ALL
SELECT 'Communities Created:', COUNT(*) FROM communities
UNION ALL
SELECT 'Users Created:', COUNT(*) FROM users
UNION ALL
SELECT 'Tournaments Created:', COUNT(*) FROM tournaments
UNION ALL
SELECT 'Tournament 1 Registrations:', COUNT(*) FROM registered_users WHERE tournament_id = 1
UNION ALL
SELECT 'Tournament 2 Registrations:', COUNT(*) FROM registered_users WHERE tournament_id = 2;

-- Show distribution of users by region
SELECT r.name as Region, COUNT(u.id) as User_Count
FROM regions r
LEFT JOIN users u ON u.region_id = r.id
GROUP BY r.id, r.name
ORDER BY User_Count DESC;
