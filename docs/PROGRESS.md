# Development Progress

| Phase | Status | Notes |
|---|---|---|
| 0 Architecture | ✅ Approved | `docs/ARCHITECTURE.md` |
| 1 Core platform | ✅ Complete | PR #1 |
| 2 Template engine | ✅ Complete | PR #2 |
| 3 Website generator | ✅ Complete | PR #3 |
| 4 Generated site CMS | ✅ Complete | PR #4 | See below. Blog and listing modules arrive with the templates that need them (Phase 7); payment keys screen with Phase 5 |
| 5 Payment integration | ✅ Built (PR #5) · ⏳ sandbox verification | All 5 gateways built and tested against a local gateway emulator. Each gateway is marked **Verified** only after a real sandbox payment on cPanel (checklist below) |
| 6 Export & deployment | ✅ Complete, awaiting review | See below. Final check on a real second cPanel account to be run by Channel Softech |
| 7 Template library | 🔄 In progress | 7a and 7b done: template 01 for all 10 categories. 7c (templates 02–10, one category per PR): Business done (PR #12, merged); Education done (PR #13, awaiting review); Hospital done (PR #14, awaiting review); E-commerce done (PR #15, awaiting review); **Real Estate done** (awaiting review); next: the remaining 5 categories (Travel, Restaurant, Hotel, Portfolio, Services) |

## Phase 7c, Real Estate: templates 02–10

### Delivered
- **Factory extended for listings** (properties now; travel packages and hotel rooms next):
  - A search panel that every hero variant shows only when the template has listings: buy/rent (or lease/sale) tabs, location, type and budget. Budgets suit the business: lakh/crore ranges for sales, monthly ranges for rentals.
  - A menu item for the listings with a dropdown of categories and "For sale / For rent" links.
  - New library sections: `listings` (featured, newest, one category or lowest price) and `listing_categories` with `tiles` or `cards` variants.
  - Two listing cards, `photo` (price on the photo, fact strip) and `classic` (fact chips, price and a round details button). The runtime listing search page, the "similar" row and the home sections all use the template's card; the listing pages pick up the template's colours, buttons and dark-mode fields.
  - Thirteen original building drawings (towers, penthouse, villa, bungalow, row houses, office, shops, warehouse, heritage building, farmhouse, cottage, open plot, plotted layout) and eleven interiors and site views for listing galleries.
  - Nine demo businesses, each with its own listings set-up: attributes, filters, enquiry form (with a dropdown and a preferred date) and "sold/let out/leased" label.
- **Nine new Real Estate templates**, each with its own demo business:

| # | Template | Prefix | Demo business | Hero / header / card / categories | Look |
|---|---|---|---|---|---|
| 02 | Coastline Villas | `cvl` | Pool villas, Goa | full-bleed cover / centred masthead / photo / tiles | Teal + sand, Cormorant Garamond |
| 03 | Navkar Developers | `nvk` | Developer, Ahmedabad (projects) | colour panel / dark bar / classic / cards | Deep blue + saffron, Montserrat |
| 04 | Meridian Spaces | `mrs` | Offices, retail and warehouses, Gurugram (lease/sale) | editorial / floating pill / classic / tiles | **Dark**, blue + lime, Space Grotesk |
| 05 | Bhoomi Layouts | `bhl` | Approved plots and farmland, Mysuru | split / info bar / photo / cards | Earth brown + leaf green, Poppins |
| 06 | HomeHive Rentals | `hhv` | Rentals and property management, Bengaluru | centred panorama / floating pill / classic / tiles | Indigo + honey, Plus Jakarta Sans |
| 07 | Backwater Residences | `bwr` | Waterfront homes for NRIs, Kochi | split / centred masthead / photo / cards | Lagoon blue + coral, DM Serif Display |
| 08 | Greenfield Township | `gft` | Integrated township, Nagpur | full-bleed cover / dark bar / classic / tiles | Forest green + sunflower, Sora |
| 09 | Hillcrest Retreats | `hcr` | Farmhouses and weekend homes, Lonavala | colour panel / info bar / photo / cards | Moss + terracotta, Fraunces |
| 10 | Heritage Homes Realty | `hhr` | Resale and heritage homes, Kolkata | editorial / centred masthead / classic / tiles | Oxblood + brass, Playfair Display, light footer |

- Every template has eight pages: Home, About, Services (process and FAQ), Team, Contact and three legal pages (privacy, terms, disclaimer), plus the listing search and listing pages. Each demo has 4 categories and 7–8 listings (6 featured, 1 sold, let or leased) with galleries, specifications, amenities and nearby places.
- All businesses, people, banks and RERA numbers are invented; RERA numbers are marked "(demo)".
- No runtime changes; runtime 1.7.0.

### Tests
`tests/phase7c_realestate_test.php` runs **213 end-to-end checks**.

- **Template files:** the same checks as the earlier categories, plus: a valid listings module, every demo attribute value matching its attribute (select options, whole numbers), an exterior and a gallery for every listing, and the chosen card and category views. Prefixes are compared against all 46 existing templates.
- **Each of the nine websites:**
  - demo listings imported
  - variants, hero copy, the listings menu with categories, the hero search (with tabs only where there is a choice)
  - categories, 6 featured and 3 new listings in the template's cards
  - all seven inner pages and the 404 page
  - the listing search page in the template's cards; category and buy/rent (or lease/sale) filters; the closed listing marked
  - a listing page with its gallery, specifications and enquiry form; an enquiry with the dropdown answer and date stored against the listing
  - a contact enquiry
- **Export:** a full export of one website, including listing images and partials.

| Server | 7c Real Estate | 7c E-commerce | 7c Hospital | 7c Education | 7c Business | 7b-3 | 7b-2 | 7b-1 | Shop | 7a | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| PHP dev server | 213/213 | 216/216 | 162/162 | 153/153 | 126/126 | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |
| Apache (cPanel-style subfolder) | 213/213 | 216/216 | 162/162 | 153/153 | 126/126 | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |

All template PHP passes the PHP 8.1 syntax check. Desktop and phone screenshots of the home, about, services, team, contact, listing search and listing pages of the nine websites show no horizontal scrolling and no script errors.

### Fixed during this phase
- **Long registration numbers overflowed on phones** on listing pages (e.g. a GujRERA number). Specification values now wrap.
- **Listing description repeated the summary** on listing pages; the description now starts with the highlights.
- **Long fact values were cut off** on photo cards (e.g. "Town planning approved"); values are shorter and truncate cleanly.
- **Rental search offered lakh/crore budgets**; rental sites now offer monthly budgets and still show sale prices as lakh/crore.

## Phase 7c, E-commerce: templates 02–10

### Delivered
- **Factory extended for shops:**
  - Shop chrome shown only when the shop is switched on: an announcement bar (free-delivery threshold, cash on delivery and the tagline; header style "No announcement bar" hides it), search and cart icons with the item count, a **Shop** menu item with a categories dropdown, and a trust strip (delivery, secure payment, returns) at the top of every footer.
  - New library sections: `products` (featured, new, on sale or one category; 2–4 columns) and `product_categories` with `tiles` or `circles` variants.
  - Two product cards, `overlay` (hover quick-add) and `plain` (add-to-cart button under the card). The runtime catalogue, category, product and related-products views use the template's card, and the runtime shop pages (catalogue, product, cart, checkout) pick up the template's buttons, chips, boxes and dark-mode fields.
  - About 40 original product drawings (kurtas, sarees, headphones, phones, jars, serums, jewellery, toys, coffee, sports gear, books, stationery…) composed into product images (two per product), category images, heroes, promo banners and about scenes.
  - Demo stores for nine niches: four categories, twelve products with GST rates, HSN codes, MRP, badges and options, coupons, shipping, COD and return rules, and a generated **Shipping & Returns** page that states each store's own terms.
- **Nine new E-commerce templates**, each with its own demo store:

| # | Template | Prefix | Demo store | Hero / header / card / categories | Look |
|---|---|---|---|---|---|
| 02 | Kesar Atelier | `kth` | Ethnic wear, Jaipur | split / centred masthead / overlay / tiles | Maroon + antique gold, Playfair Display |
| 03 | Voltbay Dark | `vlt` | Electronics, Bengaluru | centred panorama / floating pill / plain / circles | **Dark**, electric blue + orange, Space Grotesk |
| 04 | Green Basket | `gbo` | Organic grocery, Pune | colour panel / dark bar / plain / circles | Leaf green + mustard, Fraunces |
| 05 | Dew & Petal | `dwp` | Skincare, Mumbai | split / floating pill / overlay / circles | Berry + marigold on blush, DM Serif Display |
| 06 | Aurum Maison | `aur` | Jewellery, Chennai (no COD) | editorial / centred masthead / overlay / tiles | Charcoal + gold, Cormorant Garamond, square corners |
| 07 | Tiny Tales | `ttt` | Toys, Kochi | centred panorama / floating pill / plain / circles | Sky blue + sunshine, Poppins |
| 08 | Estate Roast | `erc` | Coffee and tea, Chikmagalur | full-bleed cover / dark bar / overlay / tiles | Espresso + caramel, Merriweather |
| 09 | Stride Pro | `stz` | Sports, New Delhi | colour panel / dark bar / plain / tiles | Ink + blaze orange, uppercase Montserrat |
| 10 | Paper Lane | `ppl` | Books and stationery, Kolkata | editorial / centred masthead / plain / circles | Ink blue + marigold, Lora, light footer |

- Every template has seven pages: Home, Our Story, Help & FAQ, Contact, Shipping & Returns and two legal pages, plus the runtime shop pages.
- Payment keys are never part of a demo; clients enter them in their own admin. The demo imports only catalogue, coupons and shop settings.
- No runtime changes; runtime 1.7.0.

### Tests
`tests/phase7c_ecommerce_test.php` runs **216 end-to-end checks**.

- **Template files:** the same checks as the earlier categories, plus: two images per product, a complete demo shop (GST/HSN, paise prices, options, coupons, no secrets), and the chosen product-card and category variants. Prefixes are compared against all 37 existing templates.
- **Each of the nine websites:**
  - demo catalogue imported (12 products, 4 categories, coupons)
  - variants, hero copy, Shop menu with categories, announcement bar (COD only where the store offers it), cart icon and trust strip
  - categories, featured and new products in the template's cards, prices with struck MRP and badges
  - all six inner pages, the Shipping & Returns terms and the 404 page
  - catalogue and category pages in the template's cards, a product page with its gallery
  - add to cart: cart line, subtotal and the header count; checkout page
  - a contact enquiry
- **Export:** a full export of one website, including product images and shop partials.

| Server | 7c E-commerce | 7c Hospital | 7c Education | 7c Business | 7b-3 | 7b-2 | 7b-1 | Shop | 7a | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| PHP dev server | 216/216 | 162/162 | 153/153 | 126/126 | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |
| Apache (cPanel-style subfolder) | 216/216 | 162/162 | 153/153 | 126/126 | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |

All template PHP passes the PHP 8.1 syntax check. Desktop and phone screenshots of the home, about, help, contact, catalogue, product and cart pages of the nine websites show no horizontal scrolling and no script errors.

### Fixed during this phase
- **Editorial hero rule cut through the headline** (letters such as "g" and "y"). The rule under the headline was pulled up by the row's gutter; it now sits below the text. Also fixed in Business 05 and 09, Education 08 and Hospital 04, which use the same hero.
- **Active category chip was blank** on the dark template's catalogue (white text on a white chip). Active chips now use the template's accent colours.
- **Category circles showed only the top of the picture**, so products looked tiny. The picture now fills the circle and is centred on the products.
- **Odd fifth product** under "Bestsellers": featured rows now show four products.
- Product and category drawings were enlarged; the cricket bat no longer runs out of its frame; phone-width announcement bars no longer show stray separators.
- Demo "as featured in" logos and copy no longer use names of real certification bodies or events.

## Phase 7c, Hospital: templates 02–10

### Delivered
- **Factory extended for hospitals:**
  - New library sections: `doctors`, with `cards` or `list` variants, showing a portrait, speciality tag, qualification, experience, timings and a booking button.
  - `opening_hours`, with a `panel` or `band` variant, showing the timetable, a note and an emergency highlight.
  - An `emergency` partial that every header variant shows only when the site sets an emergency number: a pulsing red pill with the number.
  - 18 clinical illustration tiles: reception, consultation, lab, imaging, theatre, ward, pharmacy, dental, eye, children, physio, Ayurveda, maternity, cardiac, emergency, X-ray, vaccination and wellness. These are composed into nine heroes.
  - Eight doctor portraits per template, in white coats with stethoscopes.
  - Demo copy for nine kinds of practice.
- **Nine new Hospital templates**, each with its own demo practice:

| # | Template | Prefix | Demo practice | Hero / header / timings | Look |
|---|---|---|---|---|---|
| 02 | Sunrise Care | `srh` | Multi-speciality hospital, Hyderabad | full-bleed cover / info bar with emergency / dark band | Medical blue + amber, Sora |
| 03 | SmileCraft Studio | `scd` | Dental studio, Mumbai | split / floating pill / panel | Aqua + peach, Jost, rounded |
| 04 | ClearSight Clinic | `cse` | Eye hospital, Madurai | editorial / dark bar with emergency / panel | Deep blue + gold, Montserrat |
| 05 | Little Hearts | `lhc` | Children’s hospital, Bengaluru | centred panorama / info bar with emergency / dark band | Periwinkle + sunflower, Poppins |
| 06 | PrimeScan Lab | `psd` | Diagnostics & imaging, New Delhi | colour panel / floating pill / panel | Teal-green + red, Space Grotesk |
| 07 | Revive Motion | `rvp` | Physiotherapy, Pune | full-bleed cover / centred masthead / panel | Forest green + sandy orange, Sora |
| 08 | Vaidya Garden | `vga` | Ayurveda centre, Thrissur | colour panel / centred masthead / dark band | Leaf green + turmeric, Cormorant Garamond |
| 09 | Mother’s Nest | `mnw` | Maternity hospital, Ahmedabad | split / info bar with emergency / panel | Berry + blush, Lora |
| 10 | Cardia Pulse | `chi` | Heart institute, Chennai | full-bleed ECG cover / info bar with emergency / dark band | **Dark**, rose red + sky blue, Manrope |

- Every template has nine pages:
  - Home
  - About (leadership and a facilities gallery)
  - Departments (six services)
  - Doctors (eight doctors with timings)
  - Packages (three priced packages)
  - Appointment (appointment form with two dropdowns and a date, the visit steps and timings)
  - Contact
  - Two legal pages
- Each template ships 27–28 original images and a `preview.jpg`.
- Practices without a 24-hour service (dental, diagnostics, physio, Ayurveda) have no emergency number, so none is shown.
- No runtime changes; runtime 1.7.0.

### Tests
`tests/phase7c_hospital_test.php` runs **162 end-to-end checks**.

- **Template files:** the same checks as the earlier categories. Prefixes are compared against all 37 existing templates.
- **Each of the nine websites:**
  - variants, hero copy, menu and legal links
  - departments, four doctor cards with portraits, the timings variant and statistics
  - the emergency number: shown and dialable where the practice has one, absent where it has none
  - all eight inner pages, eight doctors with timings and booking links, six departments, the facilities gallery and the leadership portraits
  - the 404 page
  - a contact enquiry
  - an appointment request, stored with both dropdown answers
- **Export:** a full export of one website.

| Server | 7c Hospital | 7c Education | 7c Business | 7b-3 | 7b-2 | 7b-1 | Shop | 7a | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| PHP dev server | 162/162 | 153/153 | 126/126 | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |
| Apache (cPanel-style subfolder) | 162/162 | 153/153 | 126/126 | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |

All template PHP passes the PHP 8.1 syntax check. Desktop and phone screenshots of every page of the nine websites show no horizontal scrolling and no script errors.

### Fixed during this phase
- **Menus wrapped onto two lines** when a practice had seven pages plus an emergency number. Menu links now stay on one line, tighten between 992 and 1400px, and the emergency link shows only its number in tight headers. Hospital page titles are shorter ("Doctors", "Packages", "Appointment"). The brand name no longer shrinks under the menu.
- **Featured testimonial was blank** on tinted-card templates. The tint rule for the section repainted the dark featured quote white, so white text sat on white. The featured quote's own colours now take priority.
- **Children's hospital hero:** the centred panorama crop is shifted down so the children and doctor stay in frame.
- **Small illustrations:** people in the physio, wellness, children's and maternity tiles are larger, drawn as busts.

## Phase 7c, Education: templates 02–10

### Delivered
- **Factory extended for education:**
  - New library sections: `courses` (`cards` and `list` variants), `events` (`list` and `cards`), `notices` (`board`, and `strip` for a slim bar under the hero) and `booking_form` (the admission / enquiry form with two dropdowns, a date, a message and side points).
  - Shared styles for course meta, tags and "New" badges, and select menus in light and dark moods.
  - 19 reusable education illustration tiles (classroom, laboratory, library, computer lab, sports field, art studio, music room, stage, graduation, lecture hall, online class, robotics, language, maths, campus, play area, hill campus, business analytics, exam hall), composed into nine different heroes.
  - Demo copy for nine kinds of institution.
- **Nine new Education templates**, each with its own demo institution:

| # | Template | Prefix | Demo institution | Hero / header / notices | Look |
|---|---|---|---|---|---|
| 02 | Acorn Playschool | `lac` | Preschool & day care, Pune | split / floating pill / strip | Tomato + sunshine on cream, Poppins, rounded |
| 03 | Apex Rankers | `apx` | JEE / NEET coaching, Hyderabad | colour panel / dark bar / board | Navy + signal red, Montserrat, course rows with fees |
| 04 | Circuit Campus | `mit` | Engineering college, Chennai | full-bleed cover / info bar / strip | Deep teal + amber, Sora |
| 05 | Cedar Global | `cis` | International school, Bengaluru | centred panorama / centred masthead / board | Plum + antique gold on ivory, Playfair Display |
| 06 | Forge Terminal | `cfa` | Coding bootcamp, Bengaluru | split / floating pill / strip | **Dark**, emerald + yellow, Space Grotesk |
| 07 | Raga Stage | `rsp` | Music & dance school, Kochi | full-bleed stage / centred masthead / board | Wine + marigold on cream, Lora |
| 08 | Westbridge Executive | `wsm` | Business school, Gurugram | editorial / dark bar / board | Ink + sky blue, Manrope, near-square corners |
| 09 | Lingua Bridge | `lgb` | Language & IELTS centre, Chandigarh | split / info bar / strip | Teal + coral, Plus Jakarta Sans |
| 10 | Pinecrest Heritage | `pcr` | Boarding school, Dehradun | colour panel / centred masthead / board | Slate + rust on parchment, Merriweather |

- Every template has nine pages:
  - Home
  - About (with faculty portraits)
  - Programmes (six courses with level, duration and fee)
  - Admissions (steps, three fee cards linking to the form, and an admission or enquiry form)
  - Events (three dated events, the notice board and a small gallery)
  - Gallery, Contact, and two legal pages
- Each template ships 21–22 original images and a `preview.jpg`. No runtime changes, so the runtime stays at 1.7.0.

### Tests
`tests/phase7c_education_test.php`: **153 end-to-end checks**.

- **Template files:** the same checks as Business (validator, own prefix, theme-driven CSS, images, page types, distinct designs). The prefixes are also checked against every existing template, including Business 02–10.
- **Each of the nine websites:**
  - variants, hero copy, menu and legal links
  - the notice variant with "New" badges
  - courses, events, stats and testimonials
  - all eight inner pages, six courses with images, three dated events plus the notice list
  - the team portraits and the 404 page
  - a contact enquiry
  - an admission or enquiry request with both dropdowns, stored with its answers and confirmed with the template's own success message
- **Export:** a full export of one website.

| Server | 7c Education | 7c Business | 7b-3 | 7b-2 | 7b-1 | Shop | 7a | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| PHP dev server | 153/153 | 126/126 | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |
| Apache (cPanel-style subfolder) | 153/153 | 126/126 | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |

All template PHP passes the PHP 8.1 syntax check. Desktop and phone screenshots of every page of the nine websites show no horizontal scrolling and no script errors.

### Fixed during this phase
- **Cedar 05 admission form.** Its dropdown was labelled "Start date", and the runtime validates any field whose label contains "date" as a calendar date. Every enquiry would have been rejected. The label is now "Start term", and the factory refuses to build a dropdown whose label contains "date".
- **Dropdown arrows.** Form fields set their colour with the `background` shorthand, which wiped Bootstrap's dropdown arrow. On light templates the arrow disappeared; on the dark template it tiled across the whole field. Fields now set `background-color` only.
- **Notice strip.** The list styles lived only in the board variant, so strip templates showed bullet points and run-together dates. The shared list styles moved into the base stylesheet.
- **About images.** These were cropped twice: once inside the SVG and once by the photo frame, so a clock tower filled the whole frame. The scene is now drawn full-width with headroom.
- **Hero centring and captions:**
  - The coaching hero's board text is centred, so the colour-panel crop keeps it whole.
  - Gallery captions come from the scene actually drawn in each image, instead of a list that could fall out of step.

## Phase 7c, Business: templates 02–10 and the template factory

### Delivered
- **Template factory** (`tools/template-factory`, a development tool; see its README):
  - Generates standalone templates from short JSON design specs.
  - Shared markup library with variants:
    - 5 heroes, 4 headers, 3 footers, 3 page banners
    - 4 card styles, 4 section-heading styles
    - variants for about, services, stats, process, testimonials, call to action, team, gallery, FAQ and logos
  - A token-driven stylesheet built on the `--wb-*` theme variables. It supports light and dark moods, and colours are never hard-coded.
  - Original SVG illustrations drawn per niche and recoloured to each template's palette, including team portraits and client logos.
  - Demo copy written for each niche.
  - Output is an ordinary template with its own prefix, so it can be edited by hand. Nothing depends on the factory at runtime, and it is never exported.
- **Nine new Business templates**, each with its own demo company:

| # | Template | Prefix | Demo business | Hero / header / footer | Look |
|---|---|---|---|---|---|
| 02 | Northbridge Freight | `nbf` | Logistics, Chennai | cover / info bar / columns | Deep teal + amber, Sora |
| 03 | Vertex Nightshift | `vtx` | IT services, Bengaluru | centred panorama / floating pill / centred | **Dark**, violet + cyan, Space Grotesk |
| 04 | Greenfield Harvest | `gfh` | Agro exports, Ahmedabad | colour panel / centred masthead / contact cards | Green + gold on cream, Fraunces |
| 05 | Ironclad Works | `icw` | Fabrication, Pune | editorial / dark bar / columns | Graphite + red-orange, uppercase Montserrat, square corners |
| 06 | Brightpath Talent | `bpt` | HR & recruitment, Hyderabad | split / info bar / light columns | Violet + sunflower, Plus Jakarta Sans |
| 07 | Suncrest Renewables | `scr` | Solar EPC, Coimbatore | cover / floating pill / contact cards | Ocean blue + sun yellow, Poppins |
| 08 | Crestmark Construct | `cmc` | Construction, Jaipur | colour panel / dark bar / columns | Charcoal + yellow, Jost |
| 09 | Harbor Chambers | `hbc` | Law firm, Mumbai | editorial / centred masthead / centred | Navy + bronze on ivory, Cormorant Garamond |
| 10 | Pulse Studio | `pls` | Creative agency, New Delhi | split / floating pill / centred | Ink + coral on warm paper, DM Serif Display |

- Every template ships:
  - 7 pages: home, about, services with pricing and FAQ, gallery, contact, and two legal pages
  - 17–22 original images
  - a `preview.jpg`
- No runtime changes, so the runtime version stays 1.7.0.

### Tests
`tests/phase7c_business_test.php` runs **126 end-to-end checks**.

For the template files:
- The validator reports no errors or warnings.
- Each template has its own prefix, with no placeholders or other templates' classes left behind.
- The stylesheets are theme-driven.
- Every demo image exists.
- The page types match the demos.
- The nine designs and descriptions are all distinct.

For each of the nine generated websites:
- The expected header, hero and footer variants render, with the hero copy.
- The header button, menu and legal links are present.
- The home sections render, with count-up statistics.
- Images are served.
- The inner pages work, the team shows four portraits, and the template's 404 page is used.
- A contact enquiry is submitted end to end and stored.

A full export of one factory-built site also passed: the ZIP contains the template's files and none of the factory tooling.

Results:

| Server | 7c Business | 7b-3 | 7b-2 | 7b-1 | Shop | 7a | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| PHP dev server | 126/126 | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |
| Apache (cPanel-style subfolder) | 126/126 | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |

All template PHP passes the PHP 8.1 syntax check. Desktop and phone screenshots of every page of the nine websites show no horizontal scrolling and no script errors.

### Fixed during this phase
- Cover hero: the dark overlay sat underneath the background image, so headlines over bright illustrations were hard to read. The overlay now stacks above the image and is stronger.
- A long statistic suffix ("8.4M sq ft") wrapped onto two lines. The suffix is now "M" and the unit moved into the label.

## Phase 7b, part 3: Portfolio 01 and Services 01 (template 01 for every category is now done)

### Delivered
- **Portfolio 01, "Studio Kiln"** (`templates/portfolio/portfolio_template_01`, prefix `skl`), projects on the listings module at `/work`:
  - Design: warm grey paper, black type, electric blue and acid lime, oversized Space Grotesk headlines, a scrolling ticker of disciplines (paused on hover, static for people who prefer reduced motion), a two-column project grid, disciplines shown as a large typographic index, a giant email address in the footer.
  - Case-study pages: client, year, industry, timeline, team, "What we did", "Outcomes", gallery and a project-brief form (budget, ideal start date).
  - Demo: a Bengaluru design studio with 10 projects in 4 disciplines; 27 original SVG mock-ups (packaging, app screens, posters, cans, book covers, signage).
- **Services 01, "Ledgerline Advisors"** (`templates/services/services_template_01`, prefix `ldg`), services on the listings module at `/services`:
  - Design: navy and emerald on white, Poppins headings, a split hero with an illustration and a checklist, flat spot illustrations, fee and turnaround chips, pricing packages, numbered step cards.
  - Service pages: "Ideal for", fee with a note (per return, per month…), turnaround, how we work, billing, what's included and documents needed, and a call-back request ("You are…", no date).
  - Free-consultation booking form (Phase 7a booking form, type "appointment") linked from the hero; pricing page with three packages.
  - Demo: an Ahmedabad CA firm with 10 services in 3 groups; 26 original SVG illustrations.
- **Listings module:**
  - `price_format: "none"` for catalogues without prices: no price fields in the admin (posted prices are ignored), no price filters or sorting, no price on pages.
  - New paths `work` and `services`; `menu_label` for the menu and breadcrumbs.
  - When the location filter is off, the detail page shows the location as a labelled fact ("Client:", "Ideal for:", "Route:", "Wing:") instead of with a map pin.
  - The call-back form can leave out the date.
- Font Space Grotesk bundled (SIL Open Font License). Runtime 1.7.0.

### Tests
`tests/phase7b_part3_test.php`, **37 end-to-end checks**:
- portfolio: hero ticker, menu, project grid without prices, filters, case study, the brief end to end, the admin form without prices (a posted price is ignored), the disciplines index on a normal "services" page
- services: hero link to the consultation form, service groups, cards with fees, filters (billing, budget, group), "Fees on request", the service page, the call-back request, pricing packages, the consultation booking, reserved address, header dropdown
- an earlier listing template still shows locations with the map pin

Results:

| Server | 7b-3 | 7b-2 | 7b-1 | Shop | 7a | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|---|---|---|---|---|---|---|---|---|---|---|---|
| PHP dev server | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |
| Apache (cPanel-style subfolder) | 37/37 | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |

The Phase 6 export test also passed (53/53) for both new websites, and the exported sites serve the project and service pages on the separate host. All PHP passes the PHP 8.1 syntax check. Desktop and phone screenshots of both websites show no horizontal scrolling and no script errors.

### Fixed during this phase
- Portfolio project grid: on phones the wider gutter made the page 4 px wider than the screen; the wider gutter now applies from tablet width.
- "10 work found": listing counts use the plural ("projects") while the menu can say "Work" (`menu_label`).

## Phase 7b, part 2: Restaurant 01 and Hotel 01

### Delivered
- **Food menu section** (`menu`, new core section type) for restaurants, cafés and bars:
  - Up to 80 dishes, each with a course, name, description, price text, food type (vegetarian, non-vegetarian, contains egg, vegan), spice level (mild / medium / hot), a label such as "Chef's special" and an optional photo.
  - Dishes are grouped by course. Layout "tabs" shows one course at a time (keyboard-accessible tabs; without JavaScript every course is listed with its heading); layout "list" shows all courses on the page. A key for the marks and a note ("prices exclude GST…") sit below.
  - Food-type marks follow the familiar Indian square-and-dot symbols and include text for screen readers.
- **Restaurant 01, "Saffron Table"** (`templates/restaurant/restaurant_template_01`, prefix `sfr`):
  - The first dark template: charcoal page, saffron and chilli accents, DM Serif Display headings, a header floating over a full-bleed table-spread hero, thin gold rules and dotted price leaders.
  - Pages: home (story, signature dishes, highlights, gallery, reviews, table booking), menu (21 dishes in 5 courses), about (chefs, press), events and private dining (venues and upcoming evenings), reservations (booking form with guests, time and date, opening hours, FAQ), contact.
  - Reuses the Phase 7a booking form (form type "booking"), opening hours and events sections.
  - Demo: a Mumbai restaurant; 29 original SVG illustrations (12 top-down dishes, interiors, kitchen, chefs).
- **Hotel 01, "The Lakeview Haveli"** (`templates/hotel/hotel_template_01`, prefix `lvh`), built on the listings module (path `rooms`):
  - Design: forest green and antique brass on ivory, Cormorant Garamond headings, Rajasthani arch-topped photo frames, a centred two-row header with "Rooms" in the menu, a lake-palace hero with a stay finder (room type, guests, view).
  - Room attributes: guests, bed, size, view, breakfast included (yes/no), bathroom, in-room amenities and "good to know" lists. The room page has a "Check availability" request with check-in date and number of nights. Status "Fully booked" keeps a room visible but marked.
  - Pages: home, rooms (8 demo rooms in 3 types), about, experiences, weddings and events (event enquiry that requires an email), contact with FAQ, booking and cancellation policy.
  - Demo: an Udaipur heritage hotel; 27 original SVG illustrations.
- Listings module: galleries now have a 4-photo layout; templates can hide the location filter.
- Fonts DM Serif Display and Cormorant Garamond bundled (SIL Open Font License). Runtime 1.6.0.

### Tests
`tests/phase7b_part2_test.php`, **54 end-to-end checks** on freshly generated Saffron Table and Lakeview Haveli websites:
- templates, new fonts (served by the generated site), builder previews
- restaurant pages: dark theme, menu as a list and as tabs, dish marks, booking form, opening hours, events; no listings on a restaurant site
- editing the menu in the builder: required fields, the 80-dish limit, unknown values, adding a course, photos off, list layout
- a table booking end to end: stored as "booking" with guests, time and date, notification email
- hotel pages: header, stay finder, featured rooms, every filter, the room page, an availability request stored with room, nights and check-in date
- hotel admin: generated room form (yes/no field, bed choices), add / validate / delete a suite
- the wedding enquiry requiring an email; "rooms" reserved only on the hotel website

Results:

| Server | 7b part 2 | 7b part 1 | Shop | 7a | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|---|---|---|---|---|---|---|---|---|---|---|
| PHP dev server | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |
| Apache (cPanel-style subfolder) | 54/54 | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |

The Phase 6 export test also passed (53/53) for both new websites, and the exported sites serve the menu, reservations and room pages on the separate host. All PHP passes the PHP 8.1 syntax check. Desktop and phone screenshots of both websites show no horizontal scrolling and no script errors.

### Fixed during this phase
- **Installer and OPcache:** on hosts with OPcache, the first requests after installation could still use the old settings file, so a form opened immediately after installing could be rejected as "expired" (seen intermittently in the export test with the hotel website). The installer now clears the cached copies of the files it writes.
- A menu with a single course no longer shows a redundant course heading.
- The call-to-action band on the dark template is now readable over its photo.

## Phase 7b, part 1: Real Estate 01, Travel 01 and the listings module

### Delivered
- **Listings module** (in every website's runtime; switched on by a `listings` block in the template manifest). One generic catalogue serves properties, holiday packages and, later, rooms, projects and similar:
  - **Configuration in the manifest:** URL path (`properties`, `packages`, `rooms`, `projects`…), labels, price format (`₹24,999` or `₹85 Lakh` / `₹1.25 Cr`), the status name for closed items ("Sold", "Sold out"), an enquiry form (heading, a dropdown, a date, thank-you text) and typed **attributes**: number, text, select, yes/no and list. Attribute values are stored as JSON on each listing, so a new category needs no database change.
  - **Public pages:** `/{path}` with filters built from the attributes (type, locality, buy/rent, bedrooms, duration, trip type…), price range, search, sorting and pagination; closed items stay visible but are listed last. `/{path}/{slug}` with a photo gallery and lightbox, specifications, description, lists (amenities, inclusions…), an enquiry form with the item name, call and WhatsApp buttons, and similar items. Title and meta description come from the listing.
  - **Enquiries** go to the existing enquiries inbox and notification email (form type `enquiry`) and return to the listing with the thank-you message.
  - **Website admin:** a menu entry named after the template ("Properties", "Packages") with a searchable list, an add/edit form generated from the attributes, photos from the media library (cover + gallery, reorder), rich-text description, badge, featured flag and status (active / closed / draft), plus a categories screen ("Property types", "Destinations").
  - **Sections:** `listings` (featured, newest, lowest price or one category) and `listing_categories` (tiles with counts). Template helpers `listings()`, `listingCategories()`, `listingsUrl()`, `listingUrl()`, `listingPrice()` and others; `page:listings` links to the index. Templates can supply their own card (`partials/listing-card.php`) or whole pages.
  - Site migration 007; the template's listing path is a reserved page address on that website only; the media library has a "Listings" folder; the generator smoke-tests the index and the first listing; the checklist accepts `page:listings` links.
  - The media picker from the shop admin is now shared (`admin-media-picker.js`).
- **Real Estate 01, "Keystone Realty"** (`templates/real-estate/realestate_template_01`, prefix `kst`):
  - Design: burgundy and champagne on warm stone, Sora headings, square-cornered frames with champagne rules, a full-bleed dusk hero with a tabbed **Buy / Rent search** (locality, property type, budget), property cards with the price on the photo and a beds / baths / area strip, property-type tiles, agents, loan partners and a buying-process timeline.
  - Attributes: available for, bedrooms, bathrooms, built-up area, possession, furnishing, facing, floor, parking, RERA number, amenities, nearby places. Enquiry: "Schedule a site visit" with a visit date.
  - Demo agency in Pune with 5 property types and 11 properties (2 sold); 31 original SVG illustrations.
- **Travel 01, "Horizon Trails"** (`templates/travel/travel_template_01`, prefix `hzt`):
  - Design: deep ocean and sunset orange on sand, Jost headings, generous rounding and pill buttons, a centred sunset hero with a **capsule trip finder** (destination, trip type, budget), mosaic destination tiles, package cards with a duration chip and "From ₹… per person".
  - Attributes: duration, nights, trip type, region, group size, best time, starting city, flights included, highlights, inclusions, exclusions. Day-by-day itineraries in the description. Enquiry: "Plan this trip" with travel date and number of travellers.
  - Demo tour operator in Kochi with 7 destinations and 10 packages (1 sold out); 26 original SVG illustrations.
- Fonts Sora and Jost bundled (SIL Open Font License). Runtime 1.5.0.

### Tests
`tests/phase7b_listings_test.php`, **69 end-to-end checks** on freshly generated Keystone Realty and Horizon Trails websites:
- template registration, manifest validation (an invalid listings block is refused on upload) and the builder preview
- homepage search and sections, every filter, combined filters, search, sorting, unknown type, junk input and XSS
- property and package pages: gallery, specifications, lists, enquiry form, similar items, SEO, sold items, 404
- the enquiry: stored with the item and answers, notification email, thank-you message, tampered return link
- the admin: list, search and status filter, generated form, validation, add with price / numbers / lists / images, outside images refused, HTML cleaned, unique addresses, draft, delete, categories (add, hide, delete), reserved addresses
- a website without listings

Results:

| Server | Phase 7b | Shop | Phase 7a (Edu/Hospital) | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|---|---|---|---|---|---|---|---|---|---|
| PHP dev server | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |
| Apache (cPanel-style subfolder) | 69/69 | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |

The Phase 6 export test also passed (53/53) on the Keystone Realty website: the exported site serves `/properties` and the property pages on the separate host. All PHP passes the PHP 8.1 syntax check. Desktop and phone screenshots of both websites (home, listing index, filtered index, detail pages, inner pages, 404) and the admin listing screens show no horizontal scrolling and no script errors.

### Fixed during this phase
- Phase 2 test: a page renamed to "projects" was refused once every listing word was reserved. Now only the website's own listing path (and `listings`) is reserved.
- Listing numbers and prices showed decimals ("3.00 BHK", "₹32,000.00").
- Detail gallery with 2 or 3 photos left an empty cell; it now has its own layouts on desktop and phone.
- Itinerary day headings (h4) were larger than the section heading.
- The route filter on travel packages listed long route strings; templates can now turn the location filter off (`location_filter: false`).
- Travel destinations: the large tile overlapped the next row on phones.

## Phase 7a, part 2: E-commerce 01 and the shop module

### Delivered
- **E-commerce 01, "Terra Market"** (`templates/ecommerce/ecommerce_template_01`, CSS prefix `trm`).
  - Design: terracotta and sage on cream, Playfair Display headings, capsule-shaped frames and a scrolling announcement bar built from the shop settings (free-delivery threshold, cash on delivery).
  - The header has a Shop menu with categories and a cart icon with an item count; the footer lists the shop's categories.
  - Product tiles show a second image on hover and a quick "Add to cart" button.
  - Pages: Home, Our Story, Help & FAQ, Contact, Shipping & Returns, Privacy, Terms, plus the shop pages.
  - 34 original SVG illustrations. Demo shop "Terra Market", Kochi: 4 categories, 14 products (6 with options), 2 coupons.
- **Shop module** (in every website's runtime; switched on by templates with the `ecommerce` feature, or in Shop settings):
  - **Pages:** `/shop` (categories, search, sort, pagination), `/shop/product/{slug}` (gallery, options with their own price and stock, quantity, add to cart / buy now, related products), `/shop/cart`, `/shop/checkout`, `/shop/order/{no}` and `/shop/invoice/{no}`.
  - **Cart:** kept in a signed cookie (no sessions or database rows). Prices and stock are always looked up again, quantities are capped to stock, and unavailable items are removed with a message.
  - **Coupons:** percentage (with a maximum), fixed amount, or free delivery. Each can have a minimum order, dates, a usage limit and a paused status.
  - **GST:** prices include or exclude GST (a setting). The discount is shared across lines by value, then tax is worked out per line. CGST + SGST applies within the shop's state and IGST for other states.
  - **Delivery:** a flat charge, free above a threshold, and an optional minimum order.
  - **Payment:** online through the Phase 5 gateways (Razorpay, Stripe, Cashfree, PhonePe, PayU), with a "Pay now" retry if a payment fails, or cash on delivery (optional fee and upper limit).
  - **Orders:** order numbers like `TM261107ABCDE`. Stock is taken and the coupon counted only when an order is placed: at once for cash on delivery, after payment for online orders. Cancelling returns the stock. The customer's order page is protected by a token.
  - **GST invoice:** a PDF with financial-year numbering (e.g. `TM/26-27/0001`), seller and buyer GSTIN, HSN codes and a GST summary. It is produced by a small built-in PDF writer, so no new library is needed.
  - **Emails:** order confirmation to the customer, a new-order notice to the shop, and shipped, delivered or cancelled updates with tracking.
- **Shop admin** (website admin):
  - **Orders:** filters, sales totals and CSV export. The order page has status changes (with allowed transitions), courier and tracking, "cash received", private notes, a history and the invoice.
  - **Products:** add and edit with images from the media library (upload or choose), price, "was" price, GST rate, HSN, SKU, stock tracking, options, badge, featured flag and draft status. The list has quick stock edits and a low-stock filter.
  - **Categories, Coupons, and Shop settings** (owner only): GSTIN, state, prefixes, delivery, cash on delivery, returns text.
- **New section types:** `products` (featured, newest, lowest price or one category) and `product_categories`. Template helpers: `$view->products()`, `productCategories()`, `shopUrl()`, `productUrl()`, `categoryUrl()`, `money()`, `cartCount()`, `shopToken()`, `shopEnabled()`.
- **Other changes:**
  - Site database migration 006 (shop tables; payments can belong to an order).
  - `shop` is a reserved page address.
  - The media library has a "Products" folder.
  - The generator's smoke test also loads the shop, a product and the cart.
  - Runtime 1.4.0.

### Tests
`tests/phase7a_shop_test.php`, **102 end-to-end checks** on a freshly generated Terra Market website:
- catalogue, filters and sorting
- options, stock caps and the signed cart cookie
- coupons, and the exact GST arithmetic
- checkout validation
- a cash-on-delivery order: stock, coupon, invoice numbering, both emails, the invoice PDF and the token checks
- an online order through the Razorpay emulator: a failed payment, a retry and a successful payment, with one confirmation only and IGST for another state
- admin order workflow, tracking emails, cancellation and restock, CSV
- product, category, coupon and settings management and their validation
- the shop switched off, and the template preview

Results:

| Server | Shop | Phase 7a (Edu/Hospital) | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|---|---|---|---|---|---|---|---|---|
| PHP dev server | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |
| Apache (cPanel-style subfolder) | 102/102 | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |

The Phase 6 export test also passed (53/53) on the shop website itself. The exported shop runs on the separate host with its products, cart and product pages. All PHP passes the PHP 8.1 syntax check. Desktop and phone screenshots of the store, product, cart, checkout and order pages and the admin screens show no horizontal scrolling and no script errors.

### Fixed during this phase
- On phones, the cart line total and the checkout form overflowed the screen; both now fit.
- "From" prices only show when a product's options really have different prices.
- Checkout totals now follow the chosen payment method, so the cash-on-delivery fee shows when cash on delivery is selected.
- A "not enough stock" message is now shown when adding more than is available.
- Customer-facing item amounts show the item price; the discount is shown on its own line.
- The Phase 6 test no longer assumes the Business template's form class.

### Notes / limits
- Shipping is one flat charge with a free-delivery threshold. Shipping and the cash-on-delivery fee are charged without GST (shown separately on the invoice).
- Refunds are made in the gateway's dashboard. Cancelling a paid order emails the customer that the payment will be refunded.
- Products are managed in the website's own admin (Products), also on the QA copy. The builder's section editor places product grids and categories on pages.
- No customer accounts: customers follow their order with the link in their email. Accounts can be added later if needed.

## Phase 7a, part 1: Education 01 and Hospital 01

### Delivered
- **Education 01, "Lyceum Classic"** (`templates/education/education_template_01`, CSS prefix `lyc`).
  - Design: forest green and saffron on a paper-toned background, Fraunces headings, arch-shaped frames, a school crest when no logo is uploaded.
  - Pages: Home, About, Courses, Admissions, Events, Gallery, Contact, Privacy Policy, Terms.
  - Sections: notice board, course cards, events list with date blocks, admission steps, fee cards and an admission enquiry form.
  - 20 original SVG illustrations. Demo content: "Evergreen Valley Academy", Thrissur.
- **Hospital 01, "CareLine Health"** (`templates/hospital/hospital_template_01`, CSS prefix `crl`).
  - Design: deep teal and coral on mint, Plus Jakarta Sans headings, leaf-shaped frames, a heartbeat-line motif and a navy footer.
  - A 24×7 emergency strip (header style "emergency"; "minimal" hides it) and an emergency card in the hero and footer.
  - Pages: Home, About, Departments, Doctors, Health Packages, Book Appointment, Contact, Privacy Policy, Terms.
  - Sections: quick-access bar, department tiles, doctor cards (specialty, qualification, experience, timings, booking button), OPD timetable with a highlighted emergency line, health packages, insurance-partner logos and an appointment form.
  - 20 original SVG illustrations (doctors, patients, hospital scenes and invented insurer logos). Demo content: "CareLine Hospital", Kochi.
- **New section types:** `courses`, `events` (YYYY-MM-DD dates; "hide past events" on the live site), `notices`, `doctors`, `opening_hours` and `booking_form`. Any template can use them.
- **Booking-style forms** (appointment, admission, booking, enquiry):
  - Name plus a mobile number or an email; "email required" can be switched on per form.
  - The message is optional.
  - Up to two dropdowns and a date (a real date, no past dates offered).
  - Answers are stored with the enquiry, so the site database gains migration 005, `extra_json`.
  - Answers appear in the admin's Enquiries list, in the CSV (Form and Details columns) and in the notification email, whose subject names the kind, e.g. "New appointment request".
- **Site details:** two new client-editable fields.
  - An **emergency / helpline number**.
  - A **highlight badge** (title and text), e.g. "NABH accredited". Before this, the badge could only come from demo content.
- **Fonts bundled:** Fraunces and Plus Jakarta Sans (WOFF2, SIL Open Font License). Runtime version 1.3.0.

### Tests
`tests/phase7a_http_test.php`, **68 end-to-end checks** against both generated websites:
- templates registered and active; all 18 pages render; theme assets and fonts load
- emergency number and badge: validation, rendering, and hiding when empty
- validation of the new section types; events "hide past" on the live site vs the builder preview
- appointment form: dropdowns, date rules, phone/email rule, forged token, thank-you message, notification email with answers and Reply-To, admin list, CSV
- "email required" option; admission form on the education site; the contact form's rules unchanged

Results:

| Server | Phase 7a | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|---|---|---|---|---|---|---|---|
| PHP dev server | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |
| Apache (cPanel-style subfolder) | 68/68 | 80/80 | 99/99 | 79/79 | 105/105 | 68/68 | 53/53 |

All template and runtime PHP files also pass the PHP 8.1 syntax check. Every page of both templates was screenshot at 1440 px and 390 px: no horizontal scrolling, no broken images, no unreadable buttons and no console errors.

### Fixed during this phase
- **Unreadable buttons on Education 01 (your screenshot).** The general link colour outranked the button colours, so some button text matched its background. Button rules are now scoped to the template, and the screenshot check flags any button whose text colour equals its background.
- **Images that looked missing in full-page screenshots.** These were lazy-loaded images that had not loaded yet. The screenshot script now scrolls the page first and checks every image.
- **Hero badge text.** It could not be edited after creation; it is now in Site details.
- **Notification email.** Labels of the extra answers are now HTML-escaped.

### Notes
- Each template's design direction is recorded in `docs/TEMPLATE_GUIDE.md`, so later templates in the same category look different.
- An unknown `form_type` sent to the section editor is not stored; like every select field, it falls back to the default.

## Phase 6: Export and deployment

### Delivered
- **Export & deploy page** for each website (button in the website header). The export runs in stages driven by the browser, like generation, with no cron, so large image libraries never hit a hosting time limit.
- **Full package** (first deployment), one ZIP to extract in `public_html` or any subfolder:
  - the website code, template, libraries (`vendor/`) and images
  - `database/schema.sql` + `database/data.sql`, written in PHP (no `mysqldump` needed)
  - `install.php`, plus `INSTALLATION.md`, `REQUIREMENTS.md` and `DEPLOYMENT_CHECKLIST.md`, filled in for that website
  - a separate **database-only ZIP** for hosts where the client prefers phpMyAdmin
- **Nothing secret leaves the builder.** The package's last step re-opens it and blocks the download if any builder value is found inside.
  - not exported: the builder's database credentials and server paths, the QA copy's website key (the installer creates a new one), administrator logins, login history, the payment message log, test payments, and the developer-only payment emulator setting
  - blanked: every saved encrypted value (SMTP password, payment keys); the client enters them again
  - optional: enquiries and live payments
- **Installation key:** each full package gets its own key, shown on the export page and in `DEPLOYMENT_CHECKLIST.md`. The package holds only its hash, and the installer requires it, so nobody who finds the uploaded files can install over the website. Wrong keys are limited to 10 per hour.
- **Installer (`install.php`):**
  1. checks the server: PHP 8.1+, the 8 required extensions, writable folders, and the database version (MySQL 5.7+ / MariaDB 10.3+)
  2. asks for the installation key, the database details, the table prefix (renamed safely, including foreign keys; existing tables are never replaced unless ticked) and the owner login
  3. imports the database, creates the owner, writes `config/` with a **new website key**, and detects whether clean addresses work
  4. deletes itself, the SQL files and the key file, and locks against re-running

  It runs on older PHP too, where it explains that 8.1 is needed instead of showing a blank page.
- **Update package** (for deployed websites): code only, plus `UPDATE.md`. It never contains `config/`, `uploads/`, `storage/`, data or the installer. Database changes are applied automatically the first time the admin is opened.
- **Package list:** the 5 newest packages per website are kept, each showing its installation key, size and versions; ZIP and SQL downloads are for users who can manage websites only.
- **Builder database updates:** `database/migrations/NNN_*.sql` are applied once, in order, on the first request after an upload (version in `wb_settings`). The first one extends `wb_exports`. The builder on Apache updated itself this way.

### Independence test
Docker Hub is blocked from this environment, so the "clean server" test was done three ways:

| Target | Web server | Database | Result |
|---|---|---|---|
| Separate Apache site (own folder and port) | Apache 2.4 + PHP 8.3 | new database, new user with no access to the builder database | 53/53 |
| Same host, subfolder `/shop`, prefix `shop_`, same database | Apache 2.4 + PHP 8.3 | shared with the website above (both coexist) | 53/53 |
| PHP **8.1.31** (compiled from source; the minimum version) | PHP built-in server, `.htaccess` rules emulated | its own database and user | 53/53 |
| Package exported by the **Apache** copy of the builder | Apache, subfolder `/apx`, prefix `apx_` | client database | 53/53 |

`tests/phase6_http_test.php` (53 checks) runs on each target:
- **Export:** export → download → inspect the package (required files present; no config, logs, secrets, builder passwords, paths or QA key)
- **Install:** before installing, the website sends visitors to `install.php`; wrong key, wrong database password and weak owner password are refused; installation succeeds and cleans up after itself
- **The installed website:**
  - every page, assets and the 404 page work; 9 private paths are blocked, including the guides that contain the key
  - the contact form works (signed with the new key)
  - the admin: owner sign-in, no QA logins carried over, the enquiry received, content editing, image upload
  - payment keys and the SMTP password are empty
- **Update package:** applying it keeps content, logins and configuration, and does not bring the installer back

All PHP files (runtime, builder, installer, libraries) also pass `php -l` under PHP 8.1, and a scan found no PHP 8.2+ functions or syntax.

### Regression
Phase 1 80/80, Phase 2 99/99, Phase 3 79/79, Phase 4 105/105 and Phase 5 68/68, on both the PHP dev server and Apache.

### Bugs found and fixed
- **Template version label (from Phase 3):** regenerating copied the newest template files but kept the old version number on the website (site 2 said 1.0.0 while running 1.1.1). The generator now records the version it actually copied.
- **Export page layout (from your screenshot):**
  - the packages table was cramped (the installation key broke over three lines) and is now laid out to fit at 1280–1920 px
  - older packages whose files were removed are hidden behind a link
  - "1 images" now reads "1 uploaded image"
- The list kept 6 packages instead of 5: the clean-up didn't count the export that was finishing.
- The runtime's database scripts are now split by a proper SQL splitter that understands quotes and comments, shared with the installer.

### Still to do by Channel Softech
- Run the same install on a **real second cPanel account** (Spec §20 item 18), using the full package and `DEPLOYMENT_CHECKLIST.md`.
- Payment sandbox verification (Phase 5 checklist). It can be done on that deployed website.

## Phase 5: Online payments

### Gateway status

| Gateway | API used | Built | Emulator tests | Real sandbox payment |
|---|---|---|---|---|
| Razorpay | Orders API + Standard Checkout (redirect mode), signature check, capture, webhooks | ✅ | ✅ | ⏳ Not yet verified |
| Stripe | Checkout Sessions (hosted page), webhooks (signed, 5-minute replay window) | ✅ | ✅ | ⏳ Not yet verified |
| Cashfree | PG API version 2025-01-01, JS checkout v3, webhooks | ✅ | ✅ | ⏳ Not yet verified |
| PhonePe | Standard Checkout **v2** (OAuth client credentials, `/checkout/v2/pay`, order status), webhooks | ✅ | ✅ | ⏳ Not yet verified |
| PayU | Hosted checkout (SHA-512 hash), reverse-hash check, `verify_payment` API, webhooks | ✅ | ✅ | ⏳ Not yet verified |

The build environment cannot reach any gateway's servers, so the flows were tested against `tools/payment-emulator.php`. It imitates each gateway's published API, hosted payment page and signatures. A gateway moves to **Verified** only after one real sandbox payment succeeds on the cPanel server (see "Sandbox verification checklist" below).

### Delivered
- **"Pay online" page** on every website at `/pay`, shown inside the website's own template, header and footer:
  - payment options set by the owner, each with a fixed price (e.g. "Consultation fee | 500") or a free amount within a minimum and maximum
  - the customer enters name, mobile and email (for the receipt), plus an optional note; a "Test mode" banner shows in test mode
  - buttons and menus link to it with `page:pay` (offered in the link picker and the Navigation editor); links can preselect an option (`/pay?option=1`) or suggest an amount (`/pay?amount=1500&purpose=Advance`)
  - protected by a signed form token, a honeypot field and a limit of 20 attempts per IP per 10 minutes
- **Payment flow:** the server creates the order at the gateway; the customer pays on the gateway's own page (the website never sees card or bank details); on return, the website:
  1. verifies what the gateway sent (signature or hash)
  2. **confirms with the gateway's API** (the API is the source of truth)
  3. checks that **the amount paid equals the amount asked**; a mismatch is never marked paid
  4. shows the receipt
- **Payment states:** paid, pending (for example a UPI approval still waiting; the receipt page re-checks by itself), failed, cancelled.
- **Safe status updates:**
  - a paid payment never changes again
  - forged or unverifiable messages are logged and ignored
  - repeated returns or webhooks change nothing and never send a second email
- **Webhooks** at `/pay/webhook/<gateway>`, checked with each gateway's own method, and then confirmed with the gateway's API. They catch payments where the customer closed the browser before returning to the website.
- **Emails:** a receipt to the customer and a notification to the owner, sent once per payment.
- **Receipt page:** reached through a link with an unguessable token; includes a print button.
- **Website admin → Payments:**
  - **Settings (owner):** turn payments on/off, choose the gateway, test or live mode, and separate test and live keys:
    - secrets are encrypted with the website's own key and never shown again; a blank field keeps the saved value
    - "Save and test keys" checks the keys with the gateway
    - each gateway's webhook address and setup steps are shown
    - a warning appears when the website is not on HTTPS
  - **Transactions (owner and editor):**
    - search, filter by status, mode and dates; total paid
    - details for each payment, with its full history (start, return, webhook, re-check)
    - "Re-check with gateway" (for when a customer says money was deducted); link to the customer's receipt
    - CSV export (spreadsheet-safe)
  - **Dashboard:** "Received this month" tile (live mode), with a reminder while in test mode or when keys are missing.
- **Builder:**
  - wizard step 7 reads the same gateway list as the websites, and choosing a gateway there switches the website's own settings over immediately (keys entered in the website admin are kept)
  - generated websites get the payment tables (migration 004)
  - the QA password no longer blocks the signature-checked webhook addresses, so gateway webhooks can reach QA websites
- **Runtime version 1.2.0.**
- **Hosting:** the website's `.htaccess` passes the `Authorization` header to PHP, which cPanel's PHP-FPM otherwise drops. PhonePe signs its webhooks with that header.

### Tests run
- `tests/phase5_http_test.php`: **68 end-to-end checks**, against the gateway emulator and a local mail server:
  - **settings:** keys required and checked against the mode; secrets never shown again; connection tests for all 5 gateways; wrong keys reported; **live mode never uses the emulator**
  - **pay page:** template, fixed-price options, validation, forged token
  - **Razorpay:**
    - success, with the option price used (the amount posted by the browser is ignored)
    - receipt and owner emails, sent once
    - payment history; replayed return; receipt token
    - failure; exact free amount; manual-capture account (captured automatically)
    - **amount tampering**, a **forged signature**, cancel
    - a "paid but never returned" payment confirmed by webhook (wrong signature rejected, repeated webhook harmless)
  - **Stripe:** success, cancel, webhook signature and replay window
  - **Cashfree:** success (mobile reduced to 10 digits), customer drop, webhook signature
  - **PhonePe:** success; pending UPI approval later confirmed by webhook; wrong webhook authorization rejected
  - **PayU:** hash accepted by the gateway; success; tampered response ignored; failure
  - **admin:** filters, search, CSV, dashboard, editor permissions, `page:pay` in menus, builder wizard list
- Results: dev server 68/68; **Apache 68/68**.
- Regression on both servers: Phase 1 80/80, Phase 2 99/99, Phase 3 79/79, Phase 4 105/105.
- Headless Chrome, desktop and mobile, with no JavaScript errors:
  - the real browser flow through Razorpay's and Cashfree's checkout scripts (fixed-price option locks the amount, "something else" unlocks it) → gateway page → back to the receipt
  - the admin screens, and "Save and test keys" from the browser

### Bugs found and fixed during testing
- **Mobile layout (from your screenshot):** every page of Business Template 01 scrolled sideways by 12 px on phones. Rows with `g-5` gutters overhang their container; they now use `g-4 g-lg-5`. A template rule also removed the payment page's side padding. Every page was checked at 360, 390 and 768 px with no overflow. Template version **1.1.1**.
- Emails from a website reached by IP address (QA) had an invalid sender (`no-reply@127.0.0.1`). The sender now falls back to the website's own address.
- On Apache, the QA password blocked gateway webhooks, and PHP-FPM dropped the `Authorization` header (both fixed, see above).
- A first version marked a pending payment as failed when someone posted a forged return to it. Unverifiable messages are now ignored.

### Sandbox verification checklist (to do on cPanel, one gateway at a time)
1. Generate the website, create its admin login (step 8), and make sure the QA address is on HTTPS.
2. Website admin → Payments → Settings: turn payments on, choose the gateway, keep **Test** mode, enter the gateway's **test** keys, then press **Save and test test keys**.
3. Add the webhook address shown there to the gateway's dashboard (with the same secret or username/password).
4. Open `/pay`, pay with the gateway's test card or UPI ID, and check:
   - the receipt shows "Payment successful"
   - Payments shows it as paid with a payment ID, and its history includes a webhook entry
   - the receipt and owner emails arrive
5. Pay once more and close the gateway window → the payment shows as cancelled.
6. Send me the results (or a screenshot of the payment's history). I then mark that gateway **Verified** here.

Test credentials: Razorpay test keys appear right after signup. Stripe test keys are in every account. Cashfree, PayU and PhonePe sandbox keys come from their dashboards; PhonePe sets up sandbox webhooks through their integration team.

### Notes
- Currency is INR. Refunds are made from the gateway's dashboard (the admin shows the payment ID to search for).
- E-commerce checkout (cart and orders) arrives with the e-commerce templates in Phase 7, on this same payment framework.
- `payment_emulator` in `config/config.php` is a developer-only setting. The Phase 6 export removes it and refuses to finish if it is found.

## Phase 4: The generated website's own admin panel

Every generated website now has its own admin panel at **`/admin`** (for example `https://clientdomain.com/admin`). It runs entirely from the website's own files and database; it never contacts the builder.

### Delivered
- **Sign-in and security:**
  - email + password (bcrypt); sign-in is per website (own session cookie, scoped to `/admin`, HttpOnly, SameSite=Strict); session ID regenerated at sign-in; 30-minute idle timeout
  - lockout after 5 failed attempts per email+IP, or 20 per IP, within 15 minutes; the same "Invalid email or password" for unknown emails
  - CSRF token on every change; strict Content-Security-Policy (no inline scripts), `X-Frame-Options`, `no-store`, `noindex`
  - **Forgotten password** by email: the link holds a random token (only its hash is stored), is valid for one hour and works once. The answer is the same whether or not the email exists, and requests are rate-limited
- **Roles:** **Owner** (everything) and **Editor** (pages, sections, media, menus, appearance, enquiries; no settings or administrators).
- **Dashboard:** new enquiries, active pages, visible sections and images; latest enquiries; quick actions.
- **Pages & sections, Media library, Appearance (theme + site details + SEO):** the same editor as the builder. The editing logic now lives in the website runtime (`ContentService`, `MediaService`, `SettingsService`), and the builder and every website use that one shared code, so they cannot drift apart. Rich text, the media picker, the link and icon pickers and drag-and-drop ordering all work the same in both.
- **Navigation:** header menu and footer links, set to **Automatic** (built from the pages, as before) or **Custom**:
  - choose pages and custom links (https, `#anchor`, `tel:`, `mailto:`), rename labels, drag to reorder, open in a new tab (`rel="noopener"`)
  - `javascript:` and other unsafe links are refused
  - links to a hidden page drop out of the menu; deleting a page removes its links
- **Enquiries:** every contact form submission is listed with search and a status filter (New, Read, Replied, Spam):
  - opening one shows the full message and marks it read; "Reply by email" opens the mail app and marks it replied
  - delete; CSV export (opens in Excel with the right characters; cells starting with `=`, `+`, `-` or `@` are neutralised so a visitor cannot plant a spreadsheet formula)
  - a badge in the menu shows the number of new enquiries
- **Email:** the visitor's enquiry is emailed to the notification address(es), with Reply-To set to the visitor:
  - by default through PHP `mail()`; an **SMTP** server can be configured (host, port, TLS/SSL, username, password)
  - the SMTP password is stored encrypted with the website's own key and never shown back
  - "Send test email"
  - the enquiry is always saved first; if email fails, it is logged in `storage/logs` and the list marks it with an icon
- **Settings (owner):** email settings and administrators (add, edit, deactivate, delete). Owners cannot demote, deactivate or delete themselves. Deactivating a user ends their session immediately.
- **My profile:** name, email and password (current password required).
- **Builder, review step: "Website admin" card.** It shows the admin address and existing logins, and creates the owner login (by default with the client's email) or sets a new password for an existing one:
  - the password is generated (16 characters, easy to read), shown **once** and never stored by the builder
  - setting a new password also clears a lockout
- **Generator:** copies the admin, the shared editor and the PHP libraries (`vendor/`: HTML Purifier, PHPMailer) into each website, plus its admin scripts (`assets/admin/`). `vendor/` is blocked from the web. The website checks now also open `/admin/login` and confirm `vendor/` is private.
- **Database updates for existing websites:** a migration system (`runtime/schema/migrations/NNN_*.sql`, version in the `settings` table) brings older websites up to date automatically: on regeneration, when the builder opens them, and when their admin is first opened. New in this phase: `menu_items`, and password-reset columns on `admin_users`.
- **Runtime version 1.1.0.**

### Tests run
- `tests/phase4_http_test.php`: **105 end-to-end checks** against a generated website, using a local SMTP server (`tools/mailsink.py`) to receive and inspect the emails. Covers:
  - builder card: owner login created, password format, nothing stored
  - guest redirects and 401s; security headers and cookie flags; CSRF; wrong and unknown logins; session regeneration
  - every admin page; admin scripts served from the website itself; 6 private paths blocked (`vendor/`, `core/Admin`, `core/editor`, `config/`)
  - pages and sections (live on the website immediately), media (including a fake image with PHP inside), theme, details validation
  - navigation: custom header and footer menus, order, labels, new-tab links, unsafe links refused, hidden and deleted pages, back to automatic
  - email: SMTP settings, encrypted password, test email received
  - enquiries: visitor submission → notification email received with Reply-To; listing, read/replied/spam, filters, CSV with formula neutralising, delete
  - administrators and roles: editor can edit content, but gets 403 on settings and administrators; deactivation ends the session; self-demotion refused
  - profile and password change; forgotten-password email → link → new password → link works only once
  - lockout after 5 failures (even the right password is refused); the builder's "set new password" unlocks
  - logout
- Regression: Phase 1 80/80, Phase 2 99/99, Phase 3 79/79.
- **Apache** (cPanel-style subfolder install, QA password in front of the website): Phase 4 105/105, Phase 1 80/80, Phase 2 99/99, Phase 3 79/79.
- Headless Chrome: builder card and every admin page (desktop and mobile) with no JavaScript or Content-Security-Policy errors.

### Bugs found and fixed during testing
- Password-reset requests were counted as failed logins, so a few reset requests could lock everyone on that IP out. They are now rate-limited separately.
- The admin sidebar showed the website name in white on a white background. Caught in the screenshots.

### Notes
- **Who edits what:** until a website is marked Deployed, both the builder and the website admin can edit it (they share one code path, so either sees the other's changes). After deployment, the builder no longer edits content (decision 7).
- **Blog, listings, payment keys:** these screens arrive with the modules that need them: the payment keys screen in Phase 5, blog and listings with the templates that use them in Phase 7. The admin is built so modules add their own menu entries.
- Shared hosting: for reliable delivery, set up SMTP with an address on the client's own domain (cPanel → Email Accounts). PHP `mail()` works but often lands in spam.

## Phase 3: Website generator

### Delivered
- **8-step creation wizard** (spec §5), on the website page with a stepper, Back/Next buttons and progress saved per website:
  1. **Client & website:** pick a client or create one inline (atomic: no orphan client if validation fails); name, domain, description, category.
  2. **Template:** cards with live preview. Switching template re-seeds pages and sections from the new template, keeps media, site details, logo and favicon, and takes a full backup first.
  3. **Pages**, 4. **Sections**, 5. **Media**, 6. **Theme & site details:** the Phase 2 workspace, split into separate steps.
  7. **Payments:**
     - choose online payment yes/no, then the gateway (Razorpay, PhonePe, PayU, Cashfree, Stripe) with the credentials each needs, and test or live mode
     - credentials are never stored by the builder or exported (decision 22); they're entered in the website's own admin
     - the payment flow itself is built in Phase 5
  8. **Review & generate:**
     - checklist: errors block generation, warnings don't; each item has a "Fix" link to its step
     - preview at three widths
     - Generate/Regenerate button with live step-by-step progress
     - generation history; QA link and login
     - status actions
- **Staged generator** (spec §6/§17), with no cron: the browser runs one step per request, and every step is logged.
  - **Steps:** validate → staging folder → copy engine → copy template → copy fonts/libraries → check tables → write config → verify files (checksums + `php -l` where `exec()` is allowed) → publish → website checks over HTTP → finish.
  - **Safe failure:** any failure before "publish" removes the staging folder and leaves the existing website untouched. If the checks after publishing fail, the previous version is restored automatically (or, on a first generation, the broken code is removed). The website is marked *Generation Failed* with the exact step and error, and **Retry** resumes.
  - **Concurrency:** a second generation of the same website is refused while one is running.
  - **Uploads** are never touched. Each regeneration keeps the previous code in `storage/backups/sites/` (the 3 most recent are kept).
- **Standalone website** (`generated_websites/site_NNNNNN_<slug>/`):
  - front controller and clean URLs with `.htaccess`, plus a fallback `index.php?page=` when mod_rewrite is missing
  - works at a domain root or in any subfolder
  - its own `core/`, `theme/`, `assets/` (fonts and Bootstrap), `uploads/`, `config/` (app, database, payment), `storage/`, `VERSION` and `generation.json` (file checksums)
  - `.htaccess` protection for `config/`, `core/`, `storage/` and template source files; PHP disabled in `uploads/`, and SVGs sandboxed
- **Contact forms work** on generated sites. Submissions are stored in the site's own database, with these protections (no session cookie on public pages):
  - signed timestamp token: rejects forged posts and posts sent less than 3 seconds after page load
  - honeypot field
  - per-IP rate limit
  - server-side validation

  Visitors see a thank-you or error message. Email notifications arrived in Phase 4.
- **Statuses** (spec §14): Draft → Configuration Pending (reaching the review step) → Generating → Generated / Generation Failed. Then manually Testing → Ready for Deployment → Deployed (content locked in the builder; no regeneration or deletion) → Archived / restore.
- **Settings** (Super Admin): the QA address for generated websites and a generated QA password. On the builder server, generated sites are protected with HTTP Basic Auth (`generated_websites/.htaccess` + `storage/qa/.htpasswd`). The generator's website checks log in with it.

### Tests run
- `tests/phase3_http_test.php`, **79 end-to-end checks**, covering:
  - QA settings; website + new client in one step
  - all 8 steps rendering; payment validation; the checklist blocking generation
  - full generation (all 11 steps); the standalone site serving every page by clean URL, 404 for unknown pages, assets loading, 6 private paths blocked
  - contact form: bot-speed post rejected, valid enquiry stored, invalid email rejected
  - regeneration keeping uploads
  - **deliberately broken template:** generation fails at the website checks, the previous version is restored and keeps serving, the website is marked failed, and retry succeeds after the fix
  - concurrent generation refused; viewer permissions
  - every status transition, with locks while deployed
  - template switch keeping media and details
  - delete removing the generated site
- **Independence check:** a generated site was copied to an unrelated folder (no builder files) and run on its own. Every page returned 200, assets loaded, the contact form stored submissions, and no builder paths are referenced anywhere in its code.
- **Apache** (cPanel-style subfolder install): Phase 3 78/78, Phase 1 80/80, Phase 2 99/99. Also verified there:
  - the QA site returns 401 without the password and 401 with a wrong one
  - PHP placed in a generated site's `uploads/` returns 403
  - builder routes are unaffected
- Headless Chrome: all wizard steps and the live generation progress, with no JavaScript errors.

### Bugs found and fixed during testing
- **PHP code cache (OPcache) can serve the previously compiled template files for a few seconds after regeneration.** The checks were passing against old code, so a broken template went undetected in testing. The generator now discards PHP's cached copies of the published files and waits out the cache's recheck interval before checking. Note for hosting: on servers that never recheck files (`opcache.validate_timestamps=0`), a PHP restart is needed after regeneration. Documented in `INSTALL.md`.
- Two stray closing tags in the wizard view pushed steps 7–8 out of the tab container. Caught by the browser check.
- Starting a generation while one was running returned a generic 422; it now returns a clear 409 "already in progress".

### Notes
- On the builder server, a generated site's `config/database.php` points at the builder database with its own table prefix (so the QA copy works). The Phase 6 export replaces this with the installer, so builder credentials never leave the server.

## Phase 2: Template engine

### Delivered
- **Site runtime** (`runtime/`): the CMS core every generated site will carry. It has no dependency on the builder.
  - prefixed database access, so a site can only address its own tables
  - page and section renderer that follows each template's own layouts
  - `$view` API for templates; theme CSS (bundled fonts + `--wb-*` variables)
  - seeder that builds new sites from the manifest and demo content
  - strict in-house SVG sanitiser
- **Section type library:** 16 core section types (hero, page banner, about, services, why choose us, statistics, testimonials, gallery, team, FAQ, call to action, contact, client logos, text block, process, pricing). Each has its own configurable fields: text, rich text, images, colours, links, icons, toggles, selects and repeaters with min/max items.
- **Business Template 01 "Meridian Corporate":** a complete, original design.
  - 16 section views, 7 default pages, two header and two footer styles
  - realistic demo content for a consulting firm
  - original SVG illustrations (no stock photos)
  - responsive from 390 px to desktop; count-up numbers; gentle scroll reveal
- **Template live preview:** any valid template renders with its demo content on its detail page.
- **Websites:**
  - **Create:** a "New website" form (client, name, category, template with preview cards). This creates the site's tables (`sNNNNNN_` prefix) and its folder (`generated_websites/site_NNNNNN_<slug>/uploads`), then seeds the template's pages and demo content.
  - **Workspace, Pages & sections tab:**
    - pages: add, rename, change URL, page layout, set homepage, activate/deactivate, show in menu or footer, SEO title/description/keywords, drag-and-drop order (drives the menu), delete
    - sections: add from the template's types (at the end or after a section), drag-and-drop order, show/hide, duplicate, delete
    - section editor: form generated from the schema, repeaters, rich text (TinyMCE), media picker, link picker (`page:slug`), icon picker, and style options (background colour/image, overlay, spacing, text tone, anchor), with a side-by-side live preview that refreshes on save
  - **Media library** (per website):
    - multi-upload into folders; JPG, PNG, WEBP and GIF are re-encoded, SVG is sanitised
    - alt text, folder changes, search and paging
    - replace (keeps the path, so every use updates)
    - delete with a "used in…" warning
  - **Theme:** primary/accent/background/text colours, heading and body fonts (10 bundled families), button style, corner rounding, header and footer style, logo, favicon, reset to template defaults.
  - **Site details:** name, tagline, contact details, WhatsApp, hours, address, header button, footer texts, social links (https only), default SEO.
  - **Preview tab:** any page at desktop, tablet or mobile width.
  - **Delete website:** you must type the site code to confirm. A ZIP backup (folder + JSON dump of every site table) is taken first, then only that site's tables are dropped.
- **Navigation:** Websites menu entry, "Create New Website" on the dashboard, "New website" on the client page.

### Tests run
- `tests/phase2_http_test.php`: 99–100 end-to-end checks. Highlights:
  - template validation and live preview of every page
  - assets served without any external CDN; path traversal blocked
  - website creation and seeding
  - page rules: reserved, duplicate and invalid URLs; homepage protection; ordering
  - section editing with validation: `javascript:` links, foreign or traversal image paths, and repeater limits rejected; HTML stripped; rich text sanitised
  - drag-and-drop order reflected in output
  - media: upload, SVG sanitising, fake-JPEG/PHP rejection, alt text, replace, in-use delete
  - isolation between two websites (files, images, pages)
  - theme and details reaching the rendered page
  - viewer and guest permissions
  - delete with backup
- Phase 1 suite re-run: 80/80.
- Both suites pass on the PHP dev server **and** on Apache with the builder in a `public_html` subfolder.
- Headless Chrome: rendered every page of the template at desktop and mobile widths. Drove the workspace (media upload, section edit with media picker, rich-text editor, theme change, preview) with no JavaScript errors.

### Bugs found and fixed during testing
- `SHOW TABLES LIKE ?` cannot be a native prepared statement on MariaDB. Switched to `information_schema`.
- HTML Purifier's autoloader needed its bootstrap file loaded first.
- A curly quote directly after `$slug` in a message string made PHP treat it as part of the variable name, causing a 500 error on reserved URLs. Fixed and scanned the codebase for the same pattern.
- The test client sent arrays (`ids[]`) unencoded, so reorder checks passed without testing anything. The Phase 1 category-reorder check now verifies the actual order.

### Known limitations (planned)
- Contact forms render but are disabled in the builder preview. Submissions, storage and email arrive with the site's own runtime in Phase 4.
- Navigation menus are built from pages (order + "show in menu/footer"). A separate menu editor with custom links comes with the client admin in Phase 4.
- Generated-site folders currently hold only `uploads/`. Code, configuration and the standalone front controller are produced by the generator in Phase 3.

## Phase 1: Core platform

### Delivered
- **Setup** (`public/setup.php`): requirement checks, schema + seeds, first Super Admin, writes `config/config.php`, then locks itself.
- **Authentication:**
  - bcrypt passwords (rehashed automatically when the algorithm changes)
  - session ID regenerated on login, 30-minute idle timeout, browser fingerprint check
  - rate limiting: 5 failed attempts per email+IP, or 20 per IP, within 15 minutes
- **Roles:** Super Admin, Admin, Designer, Viewer, enforced on every route on the server. Menus and buttons are hidden to match.
- **Dashboard:**
  - 8 stat cards (clients, websites, templates, categories, deployed, archived, under development, generations)
  - recent websites table with search, status filter and pagination
  - quick actions; templates per category
- **Categories:** CRUD with secure image upload, activate/deactivate, drag-and-drop ordering, search, filter, sort. Deletion is blocked while templates or websites exist. The slug is locked once templates exist (it is their folder name).
- **Templates:**
  - upload as ZIP, or register folders copied in by FTP or File Manager ("Scan folders")
  - manifest validator plus PHP security scan
  - edit (including moving to another category), activate only if valid
  - preview image and screenshots
  - duplicate, upload a new version, restore or download any version
  - delete with a backup archive
- **Clients:** CRUD, detail page with the client's websites (folder, table prefix, status) and activity, search, filter, sort, pagination.
- **Users & Roles** (Super Admin): CRUD and password reset. The system prevents removing the last Super Admin and prevents self-demotion or self-deletion.
- **My profile:** edit name and email, change password (current password required).
- **Activity log:** every create, update, delete, login and template operation, with filters.
- **Security:**
  - CSRF token on every POST; prepared statements only
  - output escaping
  - strict Content-Security-Policy with no inline scripts
  - uploads: MIME sniffing and GD re-encoding; PHP execution disabled in `public/uploads`
  - ZIP extraction: zip-slip, symlink and size guards
  - template code scan
  - private folders denied by `.htaccess`
- **cPanel support:** works with the document root on `public/`, or with the whole project in `public_html` or a subfolder.

### Not in Phase 1 (by design)
- Live template preview: needs the runtime (Phase 2).
- Website creation wizard, generation and export screens (Phases 3 and 6). The dashboard does not show buttons for features that don't exist yet.
- Real templates: Business Template 01 is built in Phase 2 as the first template that renders.

### Tests run
- `tests/phase1_http_test.php`: 80 end-to-end checks covering login, CSRF, rate limiting, every CRUD action, role enforcement, XSS escaping, malicious image upload, malicious template (security scan), zip-slip, template versions, restore, duplicate, move, delete and logout.
  - **80/80 passed** on the PHP built-in server (PHP 8.3, MariaDB 10.11).
  - **80/80 passed** on Apache 2.4 + mod_php with the project in a `public_html/wb/` subfolder (a cPanel-style install). This also confirmed that `config/`, `app/`, `storage/`, `database/`, `templates/`, `.git` and `README.md` return 403, and that PHP files in `uploads/` are not executed.
- Manual UI check in headless Chrome (desktop and mobile): no JavaScript errors.

### Bugs found and fixed during testing
- Restoring a version saved before the template's category changed failed validation. Fixed: on restore, the version now adopts the template's current category.
- Under Apache, `/templates` (a builder page) was blocked by the rule protecting the `templates/` folder. Fixed in the root `.htaccess` and `templates/.htaccess`.
- Apache turned HTTP 419 (CSRF) into 500. Fixed: CSRF failures now return 403 with a clear message.

## Log

### 2026-11-28 (Phase 7c, Real Estate)
Real Estate templates 02–10; factory listings search, menu, sections, listing cards and building drawings; 213-check test; full regression on dev and Apache.

### 2026-11-24 (Phase 7c, E-commerce)
E-commerce templates 02–10; factory shop chrome, product cards, product and category sections and product drawings; 216-check test; full regression on dev and Apache.

### 2026-11-23 (Phase 7c, Hospital)
Hospital templates 02–10; factory sections for doctors, opening hours and the emergency number; 162-check test; full regression on dev and Apache.

### 2026-11-23 (Phase 7c, Education)
Education templates 02–10; factory sections for courses, events, notices and booking forms; 153-check test; full regression on dev and Apache.

### 2026-11-23 (Phase 7c, Business)
Template factory; Business templates 02–10; 126-check test; full regression on dev and Apache.

### 2026-11-07 (Phase 7a, part 2)
- E-commerce 01 (Terra Market) and the shop module: catalogue, options, cart, coupons, GST, cash on delivery and online payment, orders admin, PDF invoices, emails. Shop test 102/102; all other suites green on the dev server and Apache.

### 2026-11-07 (Phase 7a, part 1)
- Education 01 and Hospital 01 templates, six category section types, booking-style forms with stored answers, emergency number and badge in Site details. Phase 7a test 68/68; Phases 1–6 regression green on the dev server and Apache.

### 2026-11-06 (Phase 3)
- Wizard, staged generator, standalone generated websites, QA access and statuses built and tested (see above).

### 2026-11-06 (Phase 2)
- Template engine, site runtime, website workspace and Business Template 01 built and tested (see above).

### 2026-11-06
- Phase 0: requirements reviewed (30 questions answered), architecture approved. Database decision: one builder DB with a table prefix per site.
- Phase 1: core platform built and tested (see above).
