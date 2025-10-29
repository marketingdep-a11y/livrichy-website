# Listing Import Mapping

This document outlines how payloads coming from the CRM feed map onto the
Statamic `properties` collection.

## Field mapping

| JSON path | Description | Internal field | Notes |
| --- | --- | --- | --- |
| `id` | Provider listing identifier. | `external_id` | Stored verbatim so future syncs can toggle visibility/state of the same listing. |
| `ufCrm13TitleWebsite` | Marketing title for the property card. | `title` | Required. Trimmed and collapsed to single spaces. |
| `ufCrm13DescriptionWebsite` | Long-form description shown on the listing page. | `listing_description` | Preserves paragraph breaks while trimming whitespace. |
| `ufCrm13Price` | Asking price. | `price` | Normalized to a decimal string (thousand separators are removed). |
| `ufCrm13Status` | Publication status from the CRM. | `listing_status` | Normalized to slugs: `published`, `unpublished`, `draft`, `archived`, etc. Only `published` entries remain visible on the site. |
| `ufCrm13OfferingType` | Sale or rent code. | `property_status` | `RS → sale`, `RR → rent`. Unknown values are ignored. |
| `ufCrm13PropertyType` | Property type code. | `categories` | Converted to taxonomy slugs: `AP → apartment`, `TH → townhouse`, `VH → villa`, `DX → duplex`. |
| `ufCrm13Developers` | Developer / builder name. | `developer` | Optional text field. |
| `ufCrm13Community` | Community / area name. | `community` | Stored alongside the address fields. |
| `updatedTime` | Last modification timestamp. | `date` | Normalized to `Y-m-d`. |
| `ufCrm13Bedroom` | Bedroom count. | `property_features` | Rendered as a feature row with the `bedrooms` icon. |
| `ufCrm13Bathroom` | Bathroom count. | `property_features` | Rendered as a feature row with the `bathrooms` icon. |
| `ufCrm13Parking` | Parking spot count. | `property_features` | Rendered as a feature row with the `parking` icon. |
| `ufCrm13PropertySize` | Interior/plot size. | `property_features` | Rendered as a feature row with the `size` icon. |
| `ufCrm13Geopoints` | Primary latitude/longitude pair (string or array). | `latitude` / `longitude`, `geo_points` | The first coordinate becomes the main map pin; all valid points are saved to `geo_points`. |
| `ufCrm13PhotoLinks` | Remote gallery URLs. | `photo_links` | Invalid or duplicate URLs are dropped. |
| `agent.statamic_id` / `agent_id` | Statamic agent entry identifier. | `agent` | Imported only when the feed sends a valid Statamic ID. |
| `agent.external_id` / `external_agent_id` | Source agent identifier. | `external_agent_id` | Helpful while the internal agent sync is under construction. |
| `ufCrm13SourceUrl` / `source_url` | Canonical source listing. | `source_url` | Validated and stored as-is. |

## Derived data & validation

1. Title is mandatory—payloads without it are rejected.
2. Strings are trimmed and collapsed (`Normalizer::sanitizeString`). Multiline
   content keeps intentional line breaks (`sanitizeMultiline`).
3. Numeric inputs accept localized formats with commas or spaces
   (`sanitizeFloat`, `sanitizeInteger`).
4. Latitude/longitude pairs are validated through the `GeoPoint` value object.
   Invalid points are silently ignored so they do not break the import.
5. Publication dates are parsed with PHP's `DateTimeImmutable` and stored in the
   `Y-m-d` format.
6. Feature counts are transformed into `property_features` rows with the correct
   icon handles (`bedrooms`, `bathrooms`, `parking`, `size`).
7. Duplicate photo URLs collapse to a single entry per unique URL.

## Category code reference

| Code | Taxonomy slug | Label |
| --- | --- | --- |
| `AP` | `apartment` | Apartment |
| `TH` | `townhouse` | Townhouse |
| `VH` | `villa` | Villa |
| `DX` | `duplex` | Duplex |

Extend the taxonomy if the feed introduces new property types.
