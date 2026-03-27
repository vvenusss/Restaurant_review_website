# Restaurant Review Page Layout

Based on the provided wireframe image, the restaurant detail page should be structured as follows:

## 1. Top Section: Two-Column Layout
- **Left Column (Image):**
  - Displays the primary restaurant front image or a carousel of menu/front images.
  - Takes up roughly 50% to 60% of the container width (e.g., `col-md-6` or `col-md-7`).
- **Right Column (Restaurant Info):**
  - Displays the restaurant name prominently.
  - Displays the overall rating (e.g., star icons or numerical score).
  - Can include other key details like cuisine type, price range, and address to flesh out the info panel.
  - Takes up the remaining width (e.g., `col-md-6` or `col-md-5`).

## 2. Bottom Section: Reviews
- **Full-Width Container:**
  - Positioned directly below the top two-column section.
  - Titled "Reviews Section".
  - Contains a list of individual reviews (Review 1, Review 2, Review 3).
  - Each review item should display the reviewer's name, their specific rating, the review date, and the comment text.

## 3. Implementation Details
- The layout will be implemented using Bootstrap grid classes (`row`, `col-md-6`, etc.) within a main `container`.
- The page will be named `restaurant.php` and will use mock data or URL parameters to simulate fetching a specific restaurant's details and reviews, consistent with the restarted project's architecture.
