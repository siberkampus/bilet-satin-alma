-- ===============================
--  BUS_COMPANY
-- ===============================
CREATE TABLE IF NOT EXISTS Bus_Company (
    id TEXT PRIMARY KEY,
    name TEXT UNIQUE NOT NULL,
    logo_path TEXT,
    created_at TEXT
);

-- ===============================
--  USER
-- ===============================
CREATE TABLE IF NOT EXISTS User (
    id TEXT PRIMARY KEY,
    full_name TEXT,
    email TEXT UNIQUE NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('user', 'company', 'admin')),
    password TEXT NOT NULL,
    company_id TEXT,
    balance REAL DEFAULT 800,
    created_at TEXT NOT NULL,
    FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

-- ===============================
--  COUPONS
-- ===============================
CREATE TABLE IF NOT EXISTS Coupons (
    id TEXT PRIMARY KEY,
    code TEXT NOT NULL,
    discount REAL NOT NULL,
    company_id TEXT,
    usage_limit INTEGER NOT NULL,
    expire_date TEXT NOT NULL,
    created_at TEXT,
    FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

-- ===============================
--  USER_COUPONS
-- ===============================
CREATE TABLE IF NOT EXISTS User_Coupons (
    id TEXT PRIMARY KEY,
    coupon_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    created_at TEXT,
    FOREIGN KEY (coupon_id) REFERENCES Coupons(id),
    FOREIGN KEY (user_id) REFERENCES User(id)
);

-- ===============================
--  TRIPS
-- ===============================
CREATE TABLE IF NOT EXISTS Trips (
    id TEXT PRIMARY KEY,
    company_id TEXT NOT NULL,
    destination_city TEXT NOT NULL,
    arrival_time TEXT NOT NULL,
    departure_time TEXT NOT NULL,
    departure_city TEXT NOT NULL,
    price INTEGER NOT NULL,
    capacity INTEGER NOT NULL,
    created_date TEXT,
    FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

-- ===============================
--  TICKETS
-- ===============================
CREATE TABLE IF NOT EXISTS Tickets (
    id TEXT PRIMARY KEY,
    trip_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','canceled','expired')),
    total_price INTEGER NOT NULL,
    created_at TEXT,
    FOREIGN KEY (trip_id) REFERENCES Trips(id),
    FOREIGN KEY (user_id) REFERENCES User(id)
);

-- ===============================
--  BOOKED_SEATS
-- ===============================
CREATE TABLE IF NOT EXISTS Booked_Seats (
    id TEXT PRIMARY KEY,
    ticket_id TEXT NOT NULL,
    seat_number INTEGER NOT NULL,
    created_at TEXT,
    FOREIGN KEY (ticket_id) REFERENCES Tickets(id)
);


