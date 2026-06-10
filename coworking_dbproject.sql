CREATE DATABASE IF NOT EXISTS coworking_dbproject
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE coworking_dbproject;

-- ──────────────────────────────────────────────────────
--  TABLE 1: zone
--  ERD fields: zone_id [PK], zone_name, description
-- ──────────────────────────────────────────────────────
CREATE TABLE zone (
    zone_id      INT          AUTO_INCREMENT PRIMARY KEY,
    zone_name    VARCHAR(50)  NOT NULL UNIQUE,
    description  TEXT         NULL
);

INSERT INTO zone (zone_name, description) VALUES
('Single Room',    'A private quiet desk for solo focused work. Ergonomic chair, dedicated power outlet, natural lighting.'),
('Discussion Room','Sound-managed meeting room for small teams. Whiteboard, projector-ready wall, seats up to 6.'),
('Private Office', 'Fully enclosed private office with lockable door, dedicated internet line, and storage cabinet.');

-- ──────────────────────────────────────────────────────────────
--  TABLE 2: customer
--  ERD fields: customer_id [PK], fullname, email, password, phone
-- ──────────────────────────────────────────────────────────────
CREATE TABLE customer (
    customer_id  INT          AUTO_INCREMENT PRIMARY KEY,
    fullname     VARCHAR(100) NOT NULL,
    email        VARCHAR(100) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    phone        VARCHAR(15)  NOT NULL
);

-- ──────────────────────────────────────────────────────────
--  TABLE 3: staff
--  ERD fields: staff_id [PK], fullname, email, password, role
-- ──────────────────────────────────────────────────────────
CREATE TABLE staff (
    staff_id   INT          AUTO_INCREMENT PRIMARY KEY,
    fullname   VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       VARCHAR(50)  NOT NULL DEFAULT 'staff'
);

-- Default superadmin  (password: Admin@123)
INSERT INTO staff (fullname, email, password, role)
VALUES (
    'Super Admin',
    'admin@cowork.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'superadmin'
);

-- ──────────────────────────────────────────────────────
--  TABLE 4: workspace
--  ERD fields: workspace_id [PK], zone_id [FK], workspace_name, capacity, status
-- ──────────────────────────────────────────────────────
CREATE TABLE workspace (
    workspace_id    INT          AUTO_INCREMENT PRIMARY KEY,
    zone_id         INT          NOT NULL,
    workspace_name  VARCHAR(10)  NOT NULL UNIQUE,
    capacity        INT          NOT NULL DEFAULT 1,
    status          VARCHAR(20)  NOT NULL DEFAULT 'available',
    FOREIGN KEY (zone_id) REFERENCES zone(zone_id) ON DELETE RESTRICT
);

-- Zone 1 (Single Room): 20 rooms, capacity 1
INSERT INTO workspace (zone_id, workspace_name, capacity) VALUES
(1,'S01',1),(1,'S02',1),(1,'S03',1),(1,'S04',1),(1,'S05',1),
(1,'S06',1),(1,'S07',1),(1,'S08',1),(1,'S09',1),(1,'S10',1),
(1,'S11',1),(1,'S12',1),(1,'S13',1),(1,'S14',1),(1,'S15',1),
(1,'S16',1),(1,'S17',1),(1,'S18',1),(1,'S19',1),(1,'S20',1);

-- Zone 2 (Discussion Room): 10 rooms, capacity 6
INSERT INTO workspace (zone_id, workspace_name, capacity) VALUES
(2,'D01',6),(2,'D02',6),(2,'D03',6),(2,'D04',6),(2,'D05',6),
(2,'D06',6),(2,'D07',6),(2,'D08',6),(2,'D09',6),(2,'D10',6);

-- Zone 3 (Private Office): 5 rooms, capacity 10
INSERT INTO workspace (zone_id, workspace_name, capacity) VALUES
(3,'O01',10),(3,'O02',10),(3,'O03',10),(3,'O04',10),(3,'O05',10);

-- ─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
--  TABLE 5: booking
--  ERD fields: booking_id [PK], customer_id [FK], workspace_id [FK], staff_id [FK],bbooking_date, booking_token, status,start_time, end_time, checkin_time, checkout_time
-- ─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
CREATE TABLE booking (
    booking_id     INT           AUTO_INCREMENT PRIMARY KEY,
    customer_id    INT           NOT NULL,
    workspace_id   INT           NOT NULL,
    staff_id       INT           NULL,
    booking_date   DATE          NOT NULL,
    booking_token  VARCHAR(50)   NOT NULL UNIQUE,
    status         VARCHAR(20)   NOT NULL DEFAULT 'active',
    start_time     DATETIME      NOT NULL,
    end_time       DATETIME      NOT NULL,
    checkin_time   DATETIME      NULL,
    checkout_time  DATETIME      NULL,
    FOREIGN KEY (customer_id)  REFERENCES customer(customer_id)   ON DELETE CASCADE,
    FOREIGN KEY (workspace_id) REFERENCES workspace(workspace_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id)     REFERENCES staff(staff_id)         ON DELETE SET NULL
);

-- ──────────────────────────────────────────────────────
--  INDEXES
-- ──────────────────────────────────────────────────────
CREATE INDEX idx_workspace_zone      ON workspace(zone_id);
CREATE INDEX idx_workspace_status    ON workspace(status);
CREATE INDEX idx_booking_customer    ON booking(customer_id);
CREATE INDEX idx_booking_workspace   ON booking(workspace_id);
CREATE INDEX idx_booking_staff       ON booking(staff_id);
CREATE INDEX idx_booking_status      ON booking(status);
CREATE INDEX idx_booking_times       ON booking(start_time, end_time);
