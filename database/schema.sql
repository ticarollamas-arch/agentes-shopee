CREATE TABLE IF NOT EXISTS agents (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 role VARCHAR(100) NOT NULL,
 status ENUM('available','working','waiting','offline') NOT NULL DEFAULT 'available',
 room VARCHAR(80) NOT NULL DEFAULT 'main-office',
 x_pos DECIMAL(5,2) NOT NULL DEFAULT 50,
 y_pos DECIMAL(5,2) NOT NULL DEFAULT 55,
 last_seen_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tasks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 agent_id BIGINT UNSIGNED NULL,
 title VARCHAR(180) NOT NULL,
 description TEXT NULL,
 type VARCHAR(40) NOT NULL DEFAULT 'general',
 status ENUM('queued','running','completed','failed','cancelled') NOT NULL DEFAULT 'queued',
 priority TINYINT UNSIGNED NOT NULL DEFAULT 3,
 payload_json JSON NULL,
 result_json JSON NULL,
 error_text TEXT NULL,
 created_by VARCHAR(80) NOT NULL DEFAULT 'system',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 started_at DATETIME NULL,
 finished_at DATETIME NULL,
 INDEX idx_tasks_queue(status,priority,created_at),
 CONSTRAINT fk_tasks_agent FOREIGN KEY(agent_id) REFERENCES agents(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS approvals (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 task_id BIGINT UNSIGNED NULL,
 title VARCHAR(180) NOT NULL,
 description TEXT NULL,
 impact VARCHAR(80) NOT NULL DEFAULT 'moderado',
 proposed_change_json JSON NULL,
 status ENUM('pending','approved','rejected','applied') NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 reviewed_at DATETIME NULL,
 reviewed_by VARCHAR(80) NULL,
 INDEX idx_approvals_status(status,created_at),
 CONSTRAINT fk_approval_task FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activities (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 type VARCHAR(60) NOT NULL,
 message VARCHAR(500) NOT NULL,
 meta_json JSON NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_activities_created(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 agent_id BIGINT UNSIGNED NULL,
 message VARCHAR(1000) NOT NULL,
 kind ENUM('agent','admin','system') NOT NULL DEFAULT 'system',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_messages_agent FOREIGN KEY(agent_id) REFERENCES agents(id) ON DELETE SET NULL,
 INDEX idx_messages_created(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_import_log (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 source VARCHAR(40) NOT NULL DEFAULT 'shopee',
 external_product_id VARCHAR(160) NOT NULL,
 imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 outcome ENUM('created','updated','skipped','failed') NOT NULL,
 details_json JSON NULL,
 UNIQUE KEY uq_import_source_product(source,external_product_id),
 INDEX idx_imported_at(imported_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO agents (name,role,status,room,x_pos,y_pos) VALUES
('Luna SEO','Especialista SEO','available','seo',22,58),
('Nico Shopee','Especialista de catálogo','available','shopee',48,58),
('Íris Google','Analista Google','available','google',74,58),
('Atlas','Supervisor de operações','waiting','meeting',88,80);
