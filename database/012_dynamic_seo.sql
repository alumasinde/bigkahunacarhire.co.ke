-- =========================================================
-- Phase 10.1: Dynamic SEO Content Engine
-- Everything previously hardcoded in SeoController is now DB-driven.
-- =========================================================

CREATE TABLE IF NOT EXISTS seo_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_key VARCHAR(180) NOT NULL UNIQUE,
    page_type ENUM('location','airport','service','guide','faq') NOT NULL,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    title VARCHAR(255) NOT NULL,
    meta_description VARCHAR(500) NOT NULL,
    h1 VARCHAR(255) NOT NULL,
    intro TEXT NOT NULL,
    areas_json TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_indexable TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_page_type_slug (page_type, slug),
    KEY idx_seo_pages_active (is_active, is_indexable, page_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS seo_page_related (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    label VARCHAR(160) NOT NULL,
    target_key VARCHAR(180) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (page_id) REFERENCES seo_pages(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_related_page_sort (page_id, sort_order),
    KEY idx_related_page (page_id, sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS seo_page_faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (page_id) REFERENCES seo_pages(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_faq_page_sort (page_id, sort_order),
    KEY idx_faq_page (page_id, is_active, sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS seo_page_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    heading VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (page_id) REFERENCES seo_pages(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_content_page_sort (page_id, sort_order),
    KEY idx_content_page (page_id, is_active, sort_order)
) ENGINE=InnoDB;

INSERT IGNORE INTO permissions (name, description)
VALUES ('seo.manage', 'Manage dynamic SEO pages');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name = 'super_admin' AND p.name = 'seo.manage';

-- Seed the existing Phase 10 pages. INSERT IGNORE makes this safe on re-runs.
INSERT IGNORE INTO seo_pages
(page_key,page_type,name,slug,title,meta_description,h1,intro,areas_json,sort_order)
VALUES
('locations/nairobi','location','Nairobi','nairobi','Car Hire in Nairobi | Self Drive & Chauffeur Cars | Big Kahuna','Car hire in Nairobi for business, holidays, airport trips and everyday travel. Browse self-drive and chauffeur-driven cars and book with Big Kahuna Car Hire.','Car Hire in Nairobi','Looking for reliable car hire in Nairobi? Browse our vehicle range for self-drive and chauffeur-driven trips, with convenient booking and support.','["Nairobi CBD","Westlands","Kilimani","Karen","Gigiri","Syokimau"]',10),
('locations/mombasa','location','Mombasa','mombasa','Car Hire in Mombasa | Self Drive & Chauffeur Cars | Big Kahuna','Car hire in Mombasa for holidays, business and airport travel. Explore self-drive and chauffeur-driven vehicle options with Big Kahuna Car Hire.','Car Hire in Mombasa','Planning a trip to the Coast? Explore car hire options for Mombasa, airport travel, beach holidays and business trips, with self-drive and chauffeur options.','["Mombasa CBD","Nyali","Bamburi","Shanzu","Likoni","South Coast"]',20),
('airports/jkia','airport','JKIA','jkia','Car Hire at JKIA | Nairobi Airport Car Rental | Big Kahuna','Looking for car hire near Jomo Kenyatta International Airport? Explore vehicle options, pickup arrangements and chauffeur services with Big Kahuna Car Hire.','Car Hire at JKIA','Arriving or departing through Jomo Kenyatta International Airport? Plan your Nairobi transport with a rental car or chauffeur-driven option.','["Jomo Kenyatta International Airport","Syokimau","Nairobi","Embakasi"]',30),
('airports/mombasa','airport','Mombasa Airport','mombasa','Mombasa Airport Car Hire | Car Rental at Moi Airport | Big Kahuna','Plan your Coast trip with car hire near Mombasa airport. Explore self-drive and chauffeur-driven options with Big Kahuna Car Hire.','Mombasa Airport Car Hire','Arriving at Mombasa airport? Arrange your Coast transport around your arrival time, preferred vehicle and travel plans.','["Moi International Airport","Mombasa","Nyali","Bamburi","South Coast"]',40),
('services/self-drive','service','Self Drive Car Hire','self-drive','Self Drive Car Hire in Kenya | Nairobi & Mombasa | Big Kahuna','Self-drive car hire in Kenya with vehicle options for city travel, business trips, holidays and road trips. Browse the Big Kahuna fleet.','Self Drive Car Hire in Kenya','Prefer to drive yourself? Explore self-drive vehicle options for Nairobi, Mombasa and longer Kenyan trips, subject to availability and rental requirements.','["Nairobi","Mombasa","Kenya"]',50),
('services/chauffeur','service','Chauffeur Car Hire','chauffeur','Car Hire With Driver in Kenya | Nairobi & Mombasa | Big Kahuna','Hire a car with a professional driver in Kenya for airport transfers, business travel, events and private trips in Nairobi, Mombasa and beyond.','Car Hire With Driver in Kenya','Choose chauffeur-driven transport when you want a comfortable trip without having to drive. Request a vehicle and driver for business, airport transfers, events or private travel.','["Nairobi","Mombasa","Kenya"]',60),
('services/airport-car-hire','service','Airport Car Hire','airport-car-hire','Airport Car Hire in Kenya | JKIA & Mombasa | Big Kahuna','Airport car hire for Nairobi JKIA and Mombasa travel. Arrange self-drive or chauffeur-driven transport around your flight schedule.','Airport Car Hire in Kenya','Make airport arrivals easier with a rental car or chauffeur-driven option arranged around your flight schedule.','["JKIA","Mombasa Airport","Nairobi","Mombasa"]',70),
('services/suv-hire','service','SUV Car Hire','suv-hire','SUV Car Hire in Kenya | Nairobi & Mombasa | Big Kahuna','SUV car hire in Kenya for families, business travel and road trips. Browse Big Kahuna SUVs and 4x4 options.','SUV Car Hire in Kenya','SUVs are a practical choice for families, business trips and longer journeys. Browse available SUVs and compare specifications and daily rates.','["Nairobi","Mombasa","Kenya"]',80),
('services/4x4-hire','service','4x4 Car Hire','4x4-hire','4x4 Car Hire in Kenya | Safari & Road Trip Vehicles | Big Kahuna','4x4 and rugged vehicle hire in Kenya for road trips, family travel and safari-oriented journeys. Browse available Big Kahuna vehicles.','4x4 Car Hire in Kenya','Planning a longer Kenyan road trip or need a more capable vehicle? Explore available 4x4 and SUV options and confirm route requirements before booking.','["Nairobi","Mombasa","Kenya"]',90),
('services/monthly-car-hire','service','Monthly Car Hire','monthly-car-hire','Monthly Car Hire in Kenya | Long Term Car Rental | Big Kahuna','Monthly and longer-term car hire options in Kenya for business, projects and extended stays. Ask Big Kahuna for current vehicle availability and rates.','Monthly Car Hire in Kenya','Need a vehicle for weeks or months rather than days? Ask about longer-term rental options, mileage and pricing.','["Nairobi","Mombasa","Kenya"]',100),
('requirements','guide','Rental Requirements','requirements','Car Hire Requirements in Kenya | Big Kahuna Car Hire','Learn what to prepare before hiring a car in Kenya, including identification, driving licence, booking information, deposits and vehicle-specific requirements.','Car Hire Requirements in Kenya','Requirements can vary by vehicle, rental duration and customer profile. Use this guide as a starting point and confirm exact requirements before paying or collecting a vehicle.','["Kenya","Nairobi","Mombasa"]',110),
('faq','faq','Frequently Asked Questions','faq','Car Hire FAQs in Kenya | Nairobi & Mombasa | Big Kahuna','Answers to common questions about car hire in Kenya, including self-drive, chauffeur hire, airport pickup, deposits and bookings.','Car Hire FAQs','Find quick answers about booking, self-drive, chauffeur hire, airport arrangements and rental requirements.','["Kenya","Nairobi","Mombasa"]',120);

-- Content sections
INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'Car hire for Nairobi trips','Choose a rental around the purpose of your trip. Self-drive can suit business meetings, city errands and independent travel, while chauffeur-driven hire can suit airport transfers, events and days when you prefer not to drive. Check the live fleet for vehicle specifications and advertised daily rates before booking.',10 FROM seo_pages WHERE page_key='locations/nairobi';
INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'Nairobi pickup areas','Big Kahuna can serve trips around Nairobi locations such as the CBD, Westlands, Kilimani, Karen, Gigiri and Syokimau, subject to the agreed pickup arrangement. If you are arriving through JKIA, share your flight details when requesting pickup so the team can confirm the practical handover point.',20 FROM seo_pages WHERE page_key='locations/nairobi';

INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'Car hire for Mombasa and Coast travel','A rental car can make it easier to move between Mombasa, hotels and beach destinations along the Coast. Choose a vehicle based on passenger count, luggage, road conditions and whether you want to drive yourself or travel with a chauffeur.',10 FROM seo_pages WHERE page_key='locations/mombasa';
INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'Mombasa pickup areas','Pickup can be discussed for Mombasa CBD, Nyali, Bamburi, Shanzu and other agreed Coast locations. For airport arrivals, provide your arrival time and flight details so the team can confirm the pickup arrangement before you travel.',20 FROM seo_pages WHERE page_key='locations/mombasa';

INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'JKIA car hire for Nairobi travel','If your trip starts at Jomo Kenyatta International Airport, plan the vehicle and handover around your arrival time. Self-drive and chauffeur options may be available depending on the vehicle and rental requirements.',10 FROM seo_pages WHERE page_key='airports/jkia';
INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'What to include in an airport booking','Include your arrival date, approximate landing time, flight details, preferred vehicle and contact number. Confirm the pickup point, identification requirements and any deposit before payment or collection.',20 FROM seo_pages WHERE page_key='airports/jkia';

INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'Mombasa airport car hire','For Coast trips arriving by air, choose a vehicle that fits your passengers, luggage and itinerary. Pickup or delivery arrangements should be confirmed with the team before your arrival.',10 FROM seo_pages WHERE page_key='airports/mombasa';
INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'From Mombasa airport to your Coast destination','Tell the team whether you are heading to Mombasa, Nyali, Bamburi, Shanzu, South Coast or another destination. This helps confirm the most practical vehicle and handover arrangement for your trip.',20 FROM seo_pages WHERE page_key='airports/mombasa';

INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'Self-drive car hire in Kenya','Self-drive rental gives you control over your itinerary and can work well for business travel, holidays and road trips. Vehicle eligibility, identification, driving licence, deposit, permitted routes and other terms should be confirmed before collection.',10 FROM seo_pages WHERE page_key='services/self-drive';
INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'Car hire with a driver','Chauffeur-driven rental can be useful for airport transfers, business schedules, events and private trips. Tell the team your dates, route, passenger needs and preferred vehicle so the arrangement and rate can be confirmed.',10 FROM seo_pages WHERE page_key='services/chauffeur';
INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'Airport car hire in Kenya','Airport rentals work best when the booking includes the arrival airport, date, flight time, passenger count and preferred vehicle. Confirm whether the vehicle will be delivered, collected or handled by a chauffeur before travelling.',10 FROM seo_pages WHERE page_key='services/airport-car-hire';
INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'SUV rental for family and business travel','SUVs can provide additional passenger space and luggage capacity for family trips, business travel and longer journeys. Compare seating, transmission, fuel type, daily rate and availability before booking.',10 FROM seo_pages WHERE page_key='services/suv-hire';
INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'4x4 hire for road trips','If you are planning a longer Kenyan road trip, discuss the route and intended use before booking. Confirm vehicle suitability, insurance terms, permitted routes and any mileage or usage restrictions.',10 FROM seo_pages WHERE page_key='services/4x4-hire';
INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'Long-term and monthly car hire','Monthly rental can suit projects, extended business stays and temporary vehicle needs. Longer rentals may have different rates and terms, so confirm duration, mileage, maintenance responsibilities and payment arrangements before committing.',10 FROM seo_pages WHERE page_key='services/monthly-car-hire';
INSERT IGNORE INTO seo_page_content(page_id,heading,body,sort_order)
SELECT id,'Prepare before you collect your rental','Have your identification and valid driving licence ready for self-drive hire. Depending on the vehicle and rental terms, additional verification, deposit or payment requirements may apply. Confirm the exact requirements for your booking before travelling to the pickup point.',10 FROM seo_pages WHERE page_key='requirements';

-- FAQs
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Do you offer self-drive car hire in Nairobi?','Self-drive options are available subject to vehicle availability and rental requirements.',10 FROM seo_pages WHERE page_key='locations/nairobi';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I hire a car with a driver in Nairobi?','Chauffeur-driven options can be requested for business, airport transfers, events or private trips.',20 FROM seo_pages WHERE page_key='locations/nairobi';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I arrange pickup near JKIA?','Ask the team about airport pickup or delivery arrangements when booking.',30 FROM seo_pages WHERE page_key='locations/nairobi';

INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Do you offer car hire in Mombasa?','Contact Big Kahuna to confirm current vehicle availability and pickup arrangements in Mombasa.',10 FROM seo_pages WHERE page_key='locations/mombasa';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I hire a car for a Mombasa holiday?','Rental options can be suitable for beach holidays, family trips and business travel, subject to availability and rental requirements.',20 FROM seo_pages WHERE page_key='locations/mombasa';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I request a driver in Mombasa?','Chauffeur-driven hire can be requested where available.',30 FROM seo_pages WHERE page_key='locations/mombasa';

INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I arrange car hire for an airport arrival at JKIA?','Share your flight time and preferred vehicle so the team can confirm the pickup or delivery arrangement.',10 FROM seo_pages WHERE page_key='airports/jkia';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'What should I provide for airport pickup?','Provide your arrival date, approximate time, flight details and preferred vehicle.',20 FROM seo_pages WHERE page_key='airports/jkia';

INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I request a car for airport pickup in Mombasa?','Contact Big Kahuna with your arrival details and preferred vehicle so the team can confirm current arrangements.',10 FROM seo_pages WHERE page_key='airports/mombasa';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Is chauffeur-driven airport transport available?','Chauffeur-driven airport transport can be requested where available. Confirm the service and price before booking.',20 FROM seo_pages WHERE page_key='airports/mombasa';

INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'What do I need for self-drive car hire in Kenya?','Requirements vary by vehicle and customer. Confirm identification, driving licence, deposit and other terms before booking.',10 FROM seo_pages WHERE page_key='services/self-drive';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I take a self-drive rental outside Nairobi?','Ask about your intended route before booking. Longer-distance use may be subject to vehicle and rental terms.',20 FROM seo_pages WHERE page_key='services/self-drive';

INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I hire a car with a driver for a full day?','Chauffeur hire can be arranged around the trip and vehicle required. Confirm the applicable rate before booking.',10 FROM seo_pages WHERE page_key='services/chauffeur';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Is chauffeur hire available for airport transfers?','Airport transfers can be requested. Provide flight details so the team can confirm the arrangement.',20 FROM seo_pages WHERE page_key='services/chauffeur';

INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I arrange airport pickup in advance?','Yes. Share your arrival date, time, flight details and preferred vehicle so the team can confirm the arrangement.',10 FROM seo_pages WHERE page_key='services/airport-car-hire';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Do you offer self-drive and chauffeur airport options?','Both can be requested where available. Confirm the vehicle, pickup arrangement and price before booking.',20 FROM seo_pages WHERE page_key='services/airport-car-hire';

INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'What SUV options are available?','Availability changes with bookings. Browse the live fleet for current vehicles, specifications and advertised daily rates.',10 FROM seo_pages WHERE page_key='services/suv-hire';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Are SUVs available for self-drive?','Self-drive availability depends on the vehicle and rental requirements.',20 FROM seo_pages WHERE page_key='services/suv-hire';

INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I hire a 4x4 for a Kenya road trip?','Ask about your route and intended use. Vehicle availability, insurance terms and permitted routes should be confirmed before booking.',10 FROM seo_pages WHERE page_key='services/4x4-hire';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Are 4x4 vehicles available with a driver?','Chauffeur-driven options may be available depending on the vehicle and dates.',20 FROM seo_pages WHERE page_key='services/4x4-hire';

INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Do you offer monthly car rental?','Longer-term hire can be requested. Contact Big Kahuna with the vehicle type, start date and rental period for current pricing and terms.',10 FROM seo_pages WHERE page_key='services/monthly-car-hire';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Is monthly car hire cheaper than daily rental?','Longer rentals may have different rates depending on vehicle, duration, mileage and rental terms.',20 FROM seo_pages WHERE page_key='services/monthly-car-hire';

INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'What documents should I prepare?','Prepare valid identification and a valid driving licence where self-drive is requested. Additional verification may apply.',10 FROM seo_pages WHERE page_key='requirements';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Is a security deposit required?','A deposit may apply depending on the vehicle and rental terms. Confirm the amount before booking.',20 FROM seo_pages WHERE page_key='requirements';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Do international visitors need additional documents?','Requirements can differ for visitors. Confirm accepted driving documents and identification before collection.',30 FROM seo_pages WHERE page_key='requirements';

INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'How much does car hire cost in Kenya?','Prices vary by vehicle, duration, driving option and other terms. Browse the live fleet for advertised rates.',10 FROM seo_pages WHERE page_key='faq';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I hire a car without a driver?','Self-drive options are available subject to vehicle availability and rental requirements.',20 FROM seo_pages WHERE page_key='faq';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I hire a car with a driver?','Chauffeur-driven options can be requested for airport transfers, business travel, events and private trips.',30 FROM seo_pages WHERE page_key='faq';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Can I collect a car at an airport?','Airport pickup or delivery can be requested; provide flight details so the team can confirm the arrangement.',40 FROM seo_pages WHERE page_key='faq';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'How do I book?','Browse the fleet, choose a vehicle and submit your booking details.',50 FROM seo_pages WHERE page_key='faq';
INSERT IGNORE INTO seo_page_faqs(page_id,question,answer,sort_order)
SELECT id,'Do you accept M-Pesa?','Where enabled for a booking, M-Pesa payment can be initiated through the booking flow.',60 FROM seo_pages WHERE page_key='faq';

-- Related pages are keyed by page_key, so relationships remain stable if titles change.
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire at JKIA','airports/jkia',10 FROM seo_pages WHERE page_key='locations/nairobi';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Self Drive Car Hire','services/self-drive',20 FROM seo_pages WHERE page_key='locations/nairobi';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire With Driver','services/chauffeur',30 FROM seo_pages WHERE page_key='locations/nairobi';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'View Fleet','fleet',40 FROM seo_pages WHERE page_key='locations/nairobi';

INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Mombasa Airport Car Hire','airports/mombasa',10 FROM seo_pages WHERE page_key='locations/mombasa';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'4x4 Car Hire','services/4x4-hire',20 FROM seo_pages WHERE page_key='locations/mombasa';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire With Driver','services/chauffeur',30 FROM seo_pages WHERE page_key='locations/mombasa';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'View Fleet','fleet',40 FROM seo_pages WHERE page_key='locations/mombasa';

INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Nairobi','locations/nairobi',10 FROM seo_pages WHERE page_key='airports/jkia';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Self Drive Car Hire','services/self-drive',20 FROM seo_pages WHERE page_key='airports/jkia';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire With Driver','services/chauffeur',30 FROM seo_pages WHERE page_key='airports/jkia';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Book a Car','book',40 FROM seo_pages WHERE page_key='airports/jkia';

INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Mombasa','locations/mombasa',10 FROM seo_pages WHERE page_key='airports/mombasa';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'4x4 Car Hire','services/4x4-hire',20 FROM seo_pages WHERE page_key='airports/mombasa';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire With Driver','services/chauffeur',30 FROM seo_pages WHERE page_key='airports/mombasa';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Book a Car','book',40 FROM seo_pages WHERE page_key='airports/mombasa';

INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Nairobi','locations/nairobi',10 FROM seo_pages WHERE page_key='services/self-drive';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Mombasa','locations/mombasa',20 FROM seo_pages WHERE page_key='services/self-drive';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Rental Requirements','requirements',30 FROM seo_pages WHERE page_key='services/self-drive';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'View Fleet','fleet',40 FROM seo_pages WHERE page_key='services/self-drive';

INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Nairobi','locations/nairobi',10 FROM seo_pages WHERE page_key='services/chauffeur';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Mombasa','locations/mombasa',20 FROM seo_pages WHERE page_key='services/chauffeur';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'JKIA Car Hire','airports/jkia',30 FROM seo_pages WHERE page_key='services/chauffeur';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Book a Car','book',40 FROM seo_pages WHERE page_key='services/chauffeur';

INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'JKIA Car Hire','airports/jkia',10 FROM seo_pages WHERE page_key='services/airport-car-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Mombasa Airport Car Hire','airports/mombasa',20 FROM seo_pages WHERE page_key='services/airport-car-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire With Driver','services/chauffeur',30 FROM seo_pages WHERE page_key='services/airport-car-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Book a Car','book',40 FROM seo_pages WHERE page_key='services/airport-car-hire';

INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'4x4 Car Hire','services/4x4-hire',10 FROM seo_pages WHERE page_key='services/suv-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Nairobi','locations/nairobi',20 FROM seo_pages WHERE page_key='services/suv-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Mombasa','locations/mombasa',30 FROM seo_pages WHERE page_key='services/suv-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'View Fleet','fleet',40 FROM seo_pages WHERE page_key='services/suv-hire';

INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'SUV Car Hire','services/suv-hire',10 FROM seo_pages WHERE page_key='services/4x4-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Nairobi','locations/nairobi',20 FROM seo_pages WHERE page_key='services/4x4-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Mombasa','locations/mombasa',30 FROM seo_pages WHERE page_key='services/4x4-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'View Fleet','fleet',40 FROM seo_pages WHERE page_key='services/4x4-hire';

INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Nairobi','locations/nairobi',10 FROM seo_pages WHERE page_key='services/monthly-car-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Mombasa','locations/mombasa',20 FROM seo_pages WHERE page_key='services/monthly-car-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'View Fleet','fleet',30 FROM seo_pages WHERE page_key='services/monthly-car-hire';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Contact Us','contact',40 FROM seo_pages WHERE page_key='services/monthly-car-hire';

INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Self Drive Car Hire','services/self-drive',10 FROM seo_pages WHERE page_key='requirements';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Nairobi','locations/nairobi',20 FROM seo_pages WHERE page_key='requirements';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Car Hire in Mombasa','locations/mombasa',30 FROM seo_pages WHERE page_key='requirements';
INSERT IGNORE INTO seo_page_related(page_id,label,target_key,sort_order)
SELECT id,'Book a Car','book',40 FROM seo_pages WHERE page_key='requirements';
