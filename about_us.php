<?php
// product.php
require_once __DIR__ . "/config/init.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="About MyRecipe, a community-driven platform to discover, share, and create recipes.">
    <meta name="keywords" content="recipes, food, cooking, community, MyRecipe">
    <title>About Us - MyRecipe</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/footer.css">
    <style>
        /* --- 1. Global Styles & Variables (UNCHANGED) --- */
        :root {
            --primary-color: #ff6347; /* Tomato */
            --secondary-color: #333;
            --background-color: #fdfaf7; /* Light creamy background */
            --text-color: #4a4a4a;
            --heading-font: 'Poppins', sans-serif;
            --body-font: 'Poppins', sans-serif;
            --card-bg: #ffffff;
            --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            --card-hover-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--body-font);
            line-height: 1.7;
            background-color: var(--background-color);
            color: var(--text-color);
            font-size: 1rem;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        h1, h2, h3 {
            font-family: var(--heading-font);
            font-weight: 700;
            color: var(--secondary-color);
            line-height: 1.2;
        }

        h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-align: center;
        }

        h3 {
            font-size: 1.25rem;
            margin-bottom: 10px;
        }
        
        p {
            margin-bottom: 1rem;
        }

        /* --- 2. Header (UNCHANGED) --- */
        .hero-header {
            position: relative;
            background: url('https://images.unsplash.com/photo-1490818387583-1baba5e638af?q=80&w=2071&auto=format&fit=crop') no-repeat center center/cover;
            text-align: center;
            padding: 120px 20px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
        }

        .hero-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(0deg, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.3));
        }

        .hero-header > * {
            position: relative;
            z-index: 2;
        }

        .hero-header h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: white;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
        }

        .hero-header p {
            font-size: 1.3rem;
            max-width: 600px;
            margin-bottom: 30px;
            font-weight: 400;
        }

        .cta-button {
            text-decoration: none;
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 6px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .cta-button:hover {
            background: #f9340cff;
            transform: translateY(-3px);
        }

        /* --- 3. Content Sections (UNCHANGED) --- */
        .section {
            opacity: 0; 
            transform: translateY(30px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }

        .section.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .story-section {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            align-items: center;
        }

        .story-content, .story-image {
            flex: 1;
            min-width: 300px;
        }

        .story-image img {
            width: 100%;
            border-radius: 15px;
            box-shadow: var(--card-hover-shadow);
        }

        .story-content h2 {
            text-align: left;
        }
        
        .grid {
            display: grid;
            /* Changed to two containers wide for the team section */
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 30px;
            margin-top: 40px;
        }
        
        /* For the team section specifically, we want only two columns */
        .team-grid {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            max-width: 650px; /* Constrain grid width */
            margin: 40px auto 0 auto; /* Center the two columns */
        }


        .card {
            text-align: center;
            padding: 30px 20px;
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: var(--card-hover-shadow);
        }

        .card .icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .team-card img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 4px solid #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        /* --- 4. Footer & 5. Responsive Design (UNCHANGED) --- */
        footer {
            text-align: center;
            padding: 40px 20px;
            background: #2a2a2a;
            color: #ccc;
            margin-top: 40px;
            font-size: 0.9rem;
        }

        footer .footer-links {
            margin: 20px 0;
        }
        
        footer .footer-links a {
            color: #fff;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 400;
            transition: color 0.3s ease;
        }

        footer .footer-links a:hover {
            color: var(--primary-color);
        }

        .footer-social .social-icon {
            color: white;
            font-size: 1.5rem;
            margin: 0 10px;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .footer-social .social-icon:hover {
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            h2 { font-size: 2rem; }
            .hero-header h1 { font-size: 2.5rem; }
            .hero-header p { font-size: 1.1rem; }
            .container { padding: 40px 20px; }
            .story-content h2 { text-align: center; }
        }

    </style>
</head>

<body>
    <header class="hero-header">
        <h1>Welcome to MyRecipe</h1>
        <p>Where Culinary Dreams and Community Flourish</p>
        <a href="index.php" class="cta-button">Explore Recipes</a>
    </header>

    <main>
        <div class="container section">
            <section class="story-section">
                <div class="story-content">
                    <h2>Our Story</h2>
                    <p>Born from a shared passion for food, MyRecipe is a community-driven platform to explore, share, and create recipes that inspire. We believe food brings people together, and our mission is to make cooking an enjoyable and accessible adventure for everyone.</p>
                    <p>From kitchen novices to seasoned chefs, this is your space to connect and celebrate the joy of cooking.</p>
                </div>
                <div class="story-image">
                    <img src="images/about_Us.avif" alt="A vibrant table spread with delicious food">
                </div>
            </section>
        </div>

        <div class="container section">
            <section class="offers">
                <h2>What We Offer</h2>
                <div class="grid">
                    <div class="card">
                        <div class="icon"><i class="fas fa-search-location"></i></div>
                        <h3>Discover New Recipes</h3>
                        <p>Find thousands of creative recipes from home cooks and chefs around the world.</p>
                    </div>
                    <div class="card">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <h3>Join the Community</h3>
                        <p>Share your culinary creations, photos, and connect with fellow food lovers.</p>
                    </div>
                    <div class="card">
                        <div class="icon"><i class="fas fa-shopping-basket"></i></div>
                        <h3>Buy Our Products</h3>
                        <p>Get high-quality ingredients and kitchen tools delivered right to your doorstep.</p>
                    </div>
                </div>
            </section>
        </div>

        <div class="container section">
            <section class="team">
                <h2>Meet the Team</h2>
                <div class="grid team-grid"> 
                    <div class="card team-card">
                        <img src="images/Bhavin.jpeg" alt="Meta Bhavin, Founder">
                        <h3>Meta Bhavin</h3>
                        <p>Founder & Developer</p>
                    </div>
                    <div class="card team-card">
                        <img src="images/Dushyant.jpeg" alt="Dushyant Vyas, Founder">
                        <h3>Dushyant Vyas</h3>
                        <p>Founder & Developer</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer>
    <p>&copy; 2025 MyRecipe. All rights reserved.</p>
    <a href="index.php">Home Page</a>
    <a href="about_us.php">About Us</a>
    <a href="#">Our Products</a>
    <a href="#">Terms of Service</a>
    <a href="#">Privacy Policy</a>
    <div class="footer-social">
      <a href="https://facebook.com" target="_blank" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
      <a href="https://twitter.com" target="_blank" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
      <a href="https://instagram.com" target="_blank" class="social-icon instagram"><i class="fab fa-instagram"></i></a>
    </div>
  </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('.section');

            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, {
                threshold: 0.1 
            });

            sections.forEach(section => {
                observer.observe(section);
            });
        });
    </script>

</body>
</html>