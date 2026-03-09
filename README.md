# The Rustic Atelier 🍽️

A Recipe Sharing Web Application where users can explore recipes and share their own recipes with images.

## 🚀 Features

### 👤 User Authentication

* User signup and login system
* Secure authentication using PHP sessions
* Forgot password with OTP verification via email
* Reset password functionality

### 🍳 Recipe Sharing

* Users can share their own recipes
* Upload recipe images
* View recipe details with ingredients and instructions
* Categorized recipes (Veg / Non-Veg / Cake etc.)

### 🛒 Product & Order System

* Browse available food products
* Add products to cart
* Update quantity or remove items from cart
* View total cart price
* Enter delivery address before checkout
* Cash on Delivery order system

### 📦 Address Management

* Add delivery address
* Store user address in database
* Display saved address before payment

### 📧 Email System

* Order confirmation email using PHPMailer
* Forgot password OTP email system

### 🛠️ Admin Panel

* Admin dashboard
* Add and manage recipes
* Manage products
* View user orders


## 🛠️ Tech Stack

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- PHPMailer

---

## 📸 Project Screenshots

### Home Page
![Home](screenshots/home.png)

### Login Page
![Login](screenshots/login.png)

### Share Recipe
![Share Recipe](screenshots/share_recipe.png)

### Recipe Details
![Recipe](screenshots/recipe_page.png)

---

## ⚙️ Installation

1. Clone the repository

```
git clone https://github.com/Bhavinahir07/TheRusticAtelier.git
```

2. Move project into **XAMPP htdocs**

3. Import database into **phpMyAdmin**

4. Start Apache and MySQL

5. Open in browser

```
http://localhost/TheRusticAtelier
```

---

## 📂 Project Structure

```
includes/      → database connection and backend logic
partials/      → reusable UI components
public/        → frontend files
images/        → uploaded recipe images
phpmailer/     → email functionality
```

---

## 👨‍💻 Author

Meta Bhavin  
BCA Final Year Student  
Interested in **Data Science and AI/ML**