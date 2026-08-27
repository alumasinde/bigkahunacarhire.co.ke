-- Big Kahuna Car Hire — Public Legal Pages
-- Adds editable, dynamic Privacy Policy and public Terms of Service.
-- Safe for an existing installation.

INSERT INTO settings (setting_group, setting_key, setting_value) VALUES
('legal', 'privacy_title', 'Privacy Policy'),
('legal', 'privacy_last_updated', '16 August 2026'),
('legal', 'privacy_meta_title', 'Privacy Policy | Big Kahuna Car Hire'),
('legal', 'privacy_meta_description', 'Learn how Big Kahuna Car Hire collects, uses, protects and manages customer information, booking data, payment information and review integrations.'),
('legal', 'privacy_policy',
'Big Kahuna Car Hire respects your privacy and is committed to protecting personal information provided when you browse our website, request a quote, make a booking, contact us, create a customer account, make a payment, or interact with our review services.

1. INFORMATION WE COLLECT

We may collect information needed to provide car rental services, including your first name, last name, phone number, email address, identification or passport details, driving licence details, booking dates, pickup and drop-off locations, selected vehicle, payment information, communications with our team, and information required for vehicle handover and return.

We may also collect technical information such as IP address, browser/device information, pages visited and security or diagnostic information when you use our website.

2. HOW WE USE YOUR INFORMATION

We use information to process and manage bookings, verify rental eligibility, communicate about reservations, provide customer support, process payments, manage vehicle handovers and returns, prevent fraud or misuse, maintain accounting and operational records, improve our website and services, and comply with applicable legal obligations.

3. PAYMENTS

Online payments are processed through Paystack. Big Kahuna Car Hire does not store your full card number or payment authentication credentials on our own servers. Payment processing is handled by the payment provider according to its own privacy and security practices. We retain transaction information needed to identify and reconcile a payment with a booking.

4. GOOGLE BUSINESS PROFILE AND REVIEWS

Big Kahuna Car Hire may connect its Google Business Profile to our administrative system to retrieve reviews associated with our own business listing and display them on this website. This connection is authorized by an administrator who has permission to manage the Big Kahuna Car Hire Business Profile.

Google Business Profile review information may include reviewer name, rating, review text, review date, review URL and related review metadata. We use this information to manage and display customer feedback and do not sell Google data or use it for unrelated advertising.

5. TRIPADVISOR AND OTHER REVIEW SERVICES

Where enabled, we may use an authorized third-party review service to retrieve and display reviews relating to Big Kahuna Car Hire. The information displayed is used for reputation management and customer information. Third-party services remain subject to their own terms and privacy policies.

6. SHARING INFORMATION

We may share information with service providers who help us operate the business, such as payment, hosting, communication, analytics, security and authorized review services. We may also disclose information where required by law, legal process, or to protect the rights, property or safety of Big Kahuna Car Hire, our customers or others.

We do not sell customer personal information.

7. DATA SECURITY

We use reasonable technical and organizational safeguards to protect information against unauthorized access, loss, misuse or alteration. Access to administrative systems is restricted according to user permissions, and sensitive API credentials are kept server-side.

8. DATA RETENTION

We retain booking, payment, customer and operational records for as long as reasonably necessary for business, accounting, dispute resolution, security and legal purposes. Review data may be retained while the relevant review integration is enabled or while it is needed for website and reputation management.

9. COOKIES AND SESSION DATA

Our website may use cookies or server-side session data required for login, booking workflows, security, preferences and normal website operation. Where analytics or other optional technologies are enabled, the relevant information may be described in our cookie or site settings.

10. YOUR RIGHTS

Depending on applicable Kenyan law, you may have rights regarding access to, correction of, objection to, restriction of, or deletion of personal information, subject to lawful exceptions and our legal or contractual obligations. Contact us using the details below to make a privacy request.

11. THIRD-PARTY SERVICES

Links to third-party websites and services are provided for convenience. Their privacy practices are governed by their own policies. Big Kahuna Car Hire is not responsible for the privacy practices of external websites.

12. POLICY CHANGES

We may update this Privacy Policy when our services, systems, legal obligations or third-party integrations change. The current version will be published on this page with its latest update date.

13. CONTACT

For privacy questions or requests, contact Big Kahuna Car Hire using the contact details published on this page and on our website.'),
('legal', 'terms_title', 'Terms of Service'),
('legal', 'terms_last_updated', '16 August 2026'),
('legal', 'terms_meta_title', 'Terms of Service | Big Kahuna Car Hire'),
('legal', 'terms_meta_description', 'Terms of Service governing the use of the Big Kahuna Car Hire website, accounts, bookings, payments and rental services.'),
('legal', 'terms_of_service',
'These Terms of Service govern your use of the Big Kahuna Car Hire website and related online services. By using the website, creating an account, requesting a service or making a booking, you agree to these terms.

1. ABOUT THE SERVICE

Big Kahuna Car Hire provides vehicle rental and related transport services in Kenya. Vehicle availability, pricing, locations, rental periods and service options may change and are subject to confirmation.

2. ELIGIBILITY

Customers must provide accurate information and meet the applicable age, identification, driving licence and rental requirements for the vehicle and service selected. Big Kahuna Car Hire may request additional documentation before handover.

3. BOOKINGS

A booking request is not necessarily a confirmed rental until Big Kahuna Car Hire confirms the reservation. Customers are responsible for reviewing booking dates, pickup and return locations, selected vehicle, driver option and total price before completing a booking.

4. PAYMENTS

Where online payment is available, payments are processed through Paystack. A booking deposit or other amount may be requested according to the current payment settings and booking terms. Any remaining balance must be paid according to the rental agreement before or during vehicle handover as applicable.

5. VEHICLE HANDOVER AND RETURN

The vehicle may be inspected by both the customer and Big Kahuna Car Hire before handover and on return. The customer is responsible for returning the vehicle at the agreed time, location, condition and fuel level, subject to the applicable rental agreement.

6. DAMAGE, LOSS AND LIABILITY

The customer may be responsible for damage, loss, theft, unauthorized use, traffic offences, penalties or other charges arising during the rental period, subject to the rental agreement, insurance arrangements and applicable law.

7. CANCELLATIONS AND CHANGES

Cancellation, rescheduling and refund terms depend on the booking and rental agreement. Customers should contact Big Kahuna Car Hire as soon as possible if they need to change or cancel a booking.

8. ACCEPTABLE USE

You must not use the website or rental service for unlawful activity, fraud, abuse, unauthorized access, interference with our systems, or any activity that could endanger people or property.

9. ACCOUNTS

Where customer accounts are available, you are responsible for keeping your login credentials confidential and for providing accurate account information. Notify us promptly if you suspect unauthorized access.

10. THIRD-PARTY SERVICES

The website may use third-party services including payment providers, mapping services, analytics tools and review platforms. Their own terms may apply to your interaction with those services.

11. WEBSITE CONTENT

We aim to keep vehicle information, prices, availability and other content accurate, but errors or changes may occur. We may correct information and update the website without prior notice.

12. GOVERNING LAW

These Terms of Service are governed by the laws of Kenya, subject to any mandatory rights and protections that apply to customers.

13. CONTACT

If you have questions about these Terms of Service or a booking, contact Big Kahuna Car Hire using the contact information published on the website.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP;

SELECT 'Public legal pages configured.' AS status;
