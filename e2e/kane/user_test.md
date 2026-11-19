---
url: http://localhost:8099/
max_steps: 40
---

# Add a user who can log in

## Log in as admin

Enter "admin@example.org" as the email and "12345678" as the password, then click "Login".
Verify the notes page opens with "Welcome!".

## Add a user

Click "Users" in the left sidebar, then click "Add".
Enter "Jamie Lee" as the name, "jamie@example.org" as the email, and "notes2024" as both the password and the password confirmation, then click "Save".
Verify "jamie@example.org" is in the users list.

## Log in as the new user

Click "Logout" in the left sidebar and verify the "Sign In to your account" page is shown.
Log in with "jamie@example.org" and "notes2024".
Verify the notes page opens with "Welcome!" and "Users" is not in the left sidebar.
