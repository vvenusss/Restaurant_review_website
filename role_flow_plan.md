# Role-Based Update Blueprint

The updated application should keep a shared homepage and shared base experience while differentiating behavior through role-based actions and menus. The signup flow should begin with the user selecting a user type, entering name, and entering email, followed by a continue action that reveals the second-step requirements for the chosen role. For diners, the second step should collect the password and confirmation password, while for restaurant owners the second step should collect both authentication fields and restaurant-specific business information.

The navigation bar should show `Login` and `Sign Up` only for signed-out users. After login, those options should disappear and be replaced by a profile dropdown. The diner dropdown should contain `Edit Profile` and `Log Out`. The restaurant owner dropdown should contain `Edit Restaurant` and `Log Out`. The admin dropdown should contain `Edit Profile`, `Moderation`, and `Log Out`.

The dashboard should preserve the diner view as the base structure. Restaurant owners should have an additional dashboard action that allows them to add a restaurant. Admin users should have moderation access through the dropdown and related management pages. The homepage should remain shared across roles, but when the signed-in role is admin, each review listing should also display a delete control beside the review.

A dedicated forgot-password page should be added and linked from login. The page should simulate sending reset instructions to `oruicheng2501@gmail.com`, with clear inline success and validation messaging. Separate pages should exist for editing user profile data and for admin moderation, where moderation should present user and restaurant listings with delete actions in the interface.
