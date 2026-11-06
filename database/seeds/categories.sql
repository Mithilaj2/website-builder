-- Initial 10 website categories. Safe to re-run (INSERT IGNORE on unique slug).
INSERT IGNORE INTO wb_categories (name, slug, description, status, sort_order, created_at, updated_at) VALUES
('Business / Corporate', 'business', 'Corporate and company websites: services, team, clients, enquiries.', 'active', 1, NOW(), NOW()),
('Education / School / College', 'education', 'Schools, colleges, coaching institutes: courses, admissions, faculty, events.', 'active', 2, NOW(), NOW()),
('Hospital / Healthcare', 'hospital', 'Hospitals, clinics and healthcare: departments, doctors, appointment enquiries.', 'active', 3, NOW(), NOW()),
('Real Estate', 'real-estate', 'Builders, agents and property portals: listings, projects, site-visit enquiries.', 'active', 4, NOW(), NOW()),
('E-commerce', 'ecommerce', 'Online stores: catalogue, cart, checkout, orders and payments.', 'active', 5, NOW(), NOW()),
('Tours and Travel', 'travel', 'Travel agencies and tour operators: packages, destinations, booking enquiries.', 'active', 6, NOW(), NOW()),
('Restaurant / Food', 'restaurant', 'Restaurants, cafes and caterers: menu, gallery, table reservation enquiries.', 'active', 7, NOW(), NOW()),
('Hotel / Resort', 'hotel', 'Hotels, resorts and homestays: rooms, amenities, booking enquiries.', 'active', 8, NOW(), NOW()),
('Portfolio / Personal', 'portfolio', 'Personal brands, freelancers and creatives: work, resume, contact.', 'active', 9, NOW(), NOW()),
('Service-Based Business', 'services', 'Local and professional services: offerings, pricing, quotes, enquiries.', 'active', 10, NOW(), NOW());
