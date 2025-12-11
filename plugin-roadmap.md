# Plugin Improvement Roadmap

This document outlines suggested code improvements and feature ideas for both the free and pro versions of the Shortlet bookings plugin.

## Current status
- The plugin is deployed on a client site and already includes booking form, custom post types, carousel/maps assets, and email notifications.
- The repository currently only contains the initial plugin code plus this roadmap; no CI or testing setup exists yet.
- No inline diff comments are pending—this roadmap continues the improvement planning work.

## Next steps (high-level)
1. Implement the security and validation upgrades below, then ship a beta on a staging site.
2. Add a `readme.txt`, screenshots, and translation wrappers to prepare for the WordPress.org review queue.
3. Introduce automated coding standards (PHPCS/WPCS) and a small PHPUnit suite for availability/pricing helpers.
4. Split clearly between free vs. pro features at the code level (feature flags, separate modules) before listing.

## Codebase Improvements (applies to both free and pro)
- **Security & validation:**
  - Enforce nonces on all front-end forms and AJAX endpoints.
  - Validate and sanitize every user input, including map/address fields and gallery uploads.
  - Add server-side rate limiting or honeypot fields to reduce spam form submissions.
- **Performance:**
  - Defer or conditionally enqueue assets only on pages where the shortcode or CPT output appears.
  - Add lazy loading for gallery images and map tiles.
  - Cache availability lookups and price calculations per Shortlet for a short period.
- **Code structure:**
  - Introduce autoloading via Composer or a PSR-4-compatible loader instead of multiple `require` calls.
  - Organize logic into classes (e.g., `Assets`, `Bookings`, `Availability`, `Admin`) to reduce global function usage.
  - Centralize constants and option names, and document hooks/filters in code comments.
- **Testing & CI:**
  - Add unit tests for pricing, availability, and email templating.
  - Integrate a GitHub Actions workflow for linting (PHPCS/WPCS) and running tests.
- **Internationalization:**
  - Wrap all user-facing strings in translation functions and ship a `.pot` file.
- **Accessibility:**
  - Ensure form fields have accessible labels, ARIA attributes, and clear focus styles.
- **Settings & config:**
  - Provide a settings page for currency, default emails, date formats, SMTP toggles, and map providers.

## Free Version Feature Ideas
- **Robust booking form:**
  - Multi-day date picker with blackout dates and real-time availability messages.
  - Optional guest count and notes fields with validation rules.
- **Frontend display:**
  - Shortlet list/archive shortcode with filters (price range, location, amenities) and pagination.
  - Single Shortlet enhancements: amenities icons, policies, and nearby points of interest via map pins.
- **Notifications:**
  - Admin/owner email alerts with templated HTML and placeholders.
  - Guest confirmation email with booking summary and cancellation policy link.
- **Calendars:**
  - Front-end availability calendar per Shortlet with legend (available/booked/pending).
  - ICS file download for guests to add bookings to personal calendars.
- **Payments (optional/free-friendly):**
  - Offline payment option plus hooks to connect to WooCommerce if installed (as a bridge, not bundled gateway).
- **Data portability:**
  - CSV export for bookings filtered by date/Shortlet.
- **SEO & sharing:**
  - Open Graph/meta tags for Shortlet pages and copy-link/share buttons.

## Pro Version Feature Ideas
- **Dynamic pricing & rules:**
  - Seasonal pricing tables, weekend/weekday differentials, and minimum/maximum stay rules.
  - Discount codes and long-stay discounts.
- **Advanced payments:**
  - Built-in Stripe/PayPal gateways with deposit vs. full payment options.
  - PCI-conscious tokenization and saved cards for repeat guests.
- **Owner/host tools:**
  - Multi-owner support with per-owner dashboards, payouts tracking, and permissions.
  - Per-Shortlet availability overrides and manual blocks.
- **Channel/ICal sync:**
  - Two-way iCal import/export; optional API connectors to Airbnb/Booking.com via webhooks.
- **Workflow automation:**
  - Drip email sequences (reminders, pre-arrival info, review requests).
  - Webhook triggers for CRM/zap integrations on booking events.
- **Reporting & analytics:**
  - Occupancy, ADR, revenue, and cancellation reports with date filters and CSV export.
- **UX extras:**
  - Saved guest profiles, loyalty tiers, and referral codes.
  - Front-end dashboard for guests to view/cancel bookings.

## Release considerations for WordPress.org
- Comply with WordPress.org guidelines: no remote code, no tracking without consent, no obfuscated code.
- Include a detailed `readme.txt` with FAQs, screenshots, and a changelog.
- Provide graceful fallbacks when JavaScript is disabled and ensure PHP 7.4+ compatibility.
