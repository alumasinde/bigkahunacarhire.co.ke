# Big Kahuna Car Hire — full project export

This is your complete project with everything from this chat already
applied and merged in (not a patch — this is the whole app, ready to
deploy). See `PAYSTACK_PAYMENT_UPDATE.md` and `README.md` for the
pre-existing project notes; everything below is new from this chat.

## Before you deploy

1. **`.env` is NOT included** (deliberately — see the security note in
   `PAYSTACK_PAYMENT_UPDATE.md` about the exposed test key). Copy your
   existing server `.env` back in, or start from `.env.example`.
2. **No database migration needed** — every fix uses columns that
   already existed via migrations 001–014, which are all included
   under `database/` for reference/fresh installs.
3. **`logs/` and `stats/` were stripped** from this export (rotated
   log archives and Webalizer reports — not part of the app, no
   reason to ship them back and forth).

## Everything applied in this chat, in order

1. **Paystack admin/notification bugs fixed** — admin bookings list,
   booking detail, and customer/admin payment emails & SMS no longer
   mislabel Paystack payments as "M-Pesa" with a blank receipt field.
2. **Payment receipts** — new print/Save-as-PDF receipt page for
   customers (`/book/{id}/receipt`) and staff
   (`/admin/bookings/{id}/receipt`), brand-styled, gateway-aware.
3. **Dashboard visibility** — go-live warning if a Paystack *test* key
   is live in production; "Payments this month" breakdown by gateway.
4. **Dynamic admin Payments screen** (`/admin/payments`) — filterable
   by gateway/status/date/search, a one-click "Needs Verification"
   queue with inline Verify/Reject, receipt links, live sidebar badge.
5. **Double-booking / turnaround-buffer fix**:
   - Configurable gap between bookings for the same car
     (`Settings → Rental Policy → Turnaround Buffer`, default 3h — set
     to 24 for a full one-day gap).
   - Cars marked `maintenance`/`retired` can no longer be booked
     online regardless of dates.
   - New **Extend Rental** action on the booking detail page for
     confirmed/ongoing bookings — recalculates price for the new
     duration and refuses the extension if it would run into another
     customer's booking (buffer-aware conflict check), instead of
     silently creating a double-booking.
   - Extending now **automatically emails/SMS's the customer** with
     the old/new return date, updated total, and any balance still
     owing.

Full technical detail on every change, file-by-file, is in the
patch-only `CHANGES.md` from earlier in this chat if you still have
it — everything it describes is already merged into this export.


### V5.1 deployment note
Do not upload `.env` from the development ZIP to production. Create the production `.env` on the server with production credentials.
