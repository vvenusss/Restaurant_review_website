# Revised Architecture Plan

## 1. Authentication Flow
- **Sign Up (Two-Step)**
  - **Step 1:** User enters Email (Username), Password, Confirm Password, and selects User Type (Diner or Restaurant Owner).
  - **Step 2 (Diner):** User provides their Name. (Diner ID is auto-generated).
  - **Step 2 (Restaurant Owner):** User provides Restaurant Name, Owner Name, Address, Phone Number, Type of Cuisine, Opening Hours, Price Range, and uploads (or links) images for Menu and Restaurant Front.
- **Login**
  - Standard login using Email and Password. The system checks the user type to determine dashboard capabilities.

## 2. Navigation
- **Pre-Login:** Shows "Login" and "Sign Up" links.
- **Post-Login:** The "Login" and "Sign Up" links disappear. Replaced by "Dashboard", "Restaurants" (search/browse), and "Logout".

## 3. Dashboard Roles
- **Diner (Base):** Can view restaurants, search, and manage their own profile/reviews.
- **Restaurant Owner:** Inherits diner features but includes extra options to manage their restaurant details (address, menu, hours, etc.) and view reviews specific to their restaurant.
- **Admin:** Can moderate reviews and manage users across the platform.

## 4. Database Fields Addressed
- **Diner DB:** `diner_id` (auto), `name`, `email`, `password`.
- **Restaurant DB:** `rest_id` (auto), `restaurant_name`, `owner_name`, `address`, `menu_images`, `front_image`, `phone_number`, `type_of_cuisine`, `opening_hours`, `price_range`.
- **User DB (Main):** `email`, `password`, `type_of_user`.
