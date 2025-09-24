<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - CueSports Kenya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'cue-green': '#1B4332',
                        'cue-green-light': '#2D5A3D',
                        'cue-green-dark': '#081C15',
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav id="navbar" class="bg-cue-green-dark fixed w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 id="nav-logo" class="text-2xl font-bold text-white transition-colors duration-300">CueSports Kenya</h1>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/" id="nav-link-1" class="text-white hover:text-green-300 transition duration-300">Home</a>
                    <a href="/features" id="nav-link-2" class="text-white hover:text-green-300 transition duration-300">Features</a>
                    <a href="/about" id="nav-link-3" class="text-white hover:text-green-300 transition duration-300">About</a>
                    <a href="/contact" id="nav-link-4" class="text-white hover:text-green-300 transition duration-300">Contact</a>
                    <a href="/blog" id="nav-blog" class="bg-white text-cue-green px-4 py-2 rounded-lg hover:bg-gray-100 transition duration-300">Blog</a>
                </div>
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-white hover:text-green-300">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="md:hidden hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-cue-green-dark">
                    <a href="/" class="text-white hover:text-green-300 block px-3 py-2 text-base font-medium">Home</a>
                    <a href="/features" class="text-white hover:text-green-300 block px-3 py-2 text-base font-medium">Features</a>
                    <a href="/about" class="text-white hover:text-green-300 block px-3 py-2 text-base font-medium">About</a>
                    <a href="/contact" class="text-white hover:text-green-300 block px-3 py-2 text-base font-medium">Contact</a>
                    <a href="/blog" class="bg-white text-cue-green block px-3 py-2 text-base font-medium rounded-lg mx-3 text-center">Blog</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- About Content -->
    <main class="pt-16 bg-white min-h-screen">
        <!-- Header Section -->
        <section class="py-20 bg-cue-green-dark">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h1 class="text-5xl font-bold text-white mb-6">About CueSports Kenya</h1>
                    <p class="text-xl text-gray-300 leading-relaxed">
                        Transforming pool from a casual pastime into a structured career pathway 
                        for talented players across Kenya.
                    </p>
                </div>
            </div>
        </section>

        <!-- Mission & Vision Section -->
        <section class="py-16 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Our Mission -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <div class="bg-cue-green text-white w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-bullseye text-3xl"></i>
                        </div>
                        <h2 class="text-4xl font-bold text-gray-900 mb-6">Our Mission</h2>
                        <p class="text-xl text-gray-600 leading-relaxed max-w-4xl mx-auto">
                            To establish Kenya as a leading destination for professional pool sports by creating 
                            comprehensive career pathways, fostering community engagement, and maintaining 
                            world-class tournament standards that enable talented players to build sustainable 
                            careers while contributing to a thriving pool culture nationwide.
                        </p>
                    </div>
                </div>

                <!-- Our Vision -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <div class="bg-cue-green text-white w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-eye text-3xl"></i>
                        </div>
                        <h2 class="text-4xl font-bold text-gray-900 mb-6">Our Vision</h2>
                        <p class="text-xl text-gray-600 leading-relaxed max-w-4xl mx-auto">
                            A Kenya where pool is recognized as a legitimate professional sport, where every 
                            talented player has access to structured development opportunities, and where 
                            vibrant pool communities exist in every county, contributing to the nation's 
                            sporting excellence and cultural diversity.
                        </p>
                    </div>
                </div>

                <!-- Our Story -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <h2 class="text-4xl font-bold text-gray-900 mb-6">Our Story</h2>
                    </div>
                    
                    <div class="prose prose-lg max-w-4xl mx-auto">
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            CueSports Kenya was born from a simple observation: Kenya has incredible pool talent, 
                            but lacked the structured ecosystem needed to nurture and develop these skills into 
                            professional careers. Too many talented players remained in informal settings without 
                            access to organized competition, professional development, or career opportunities.
                        </p>
                        
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            Founded by passionate pool enthusiasts and sports management professionals, we set out 
                            to change this narrative. We began by partnering with local pool halls, organizing 
                            small community tournaments, and gradually building a network of players, venues, 
                            and supporters who shared our vision.
                        </p>
                        
                        <p class="text-lg text-gray-700 leading-relaxed mb-6">
                            Today, CueSports Kenya has evolved into a comprehensive ecosystem that spans from 
                            grassroots community engagement to national championship tournaments. We've created 
                            pathways for players to progress from local competitions to professional careers, 
                            while maintaining the highest standards of integrity and sportsmanship.
                        </p>
                        
                        <p class="text-lg text-gray-700 leading-relaxed">
                            Our journey continues as we expand our reach, develop new programs, and work towards 
                            our ultimate goal: making Kenya a recognized powerhouse in international pool sports 
                            while creating sustainable career opportunities for our talented players.
                        </p>
                    </div>
                </div>

                <!-- Our Values -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <h2 class="text-4xl font-bold text-gray-900 mb-6">Our Core Values</h2>
                        <p class="text-xl text-gray-600">
                            The principles that guide everything we do
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div class="text-center">
                            <div class="bg-cue-green text-white w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-handshake text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Integrity</h3>
                            <p class="text-gray-600">
                                We maintain the highest standards of fairness, transparency, and ethical conduct 
                                in all our tournaments and operations.
                            </p>
                        </div>
                        
                        <div class="text-center">
                            <div class="bg-cue-green text-white w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-users text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Community</h3>
                            <p class="text-gray-600">
                                We believe in the power of community to nurture talent, share knowledge, 
                                and create lasting relationships among pool enthusiasts.
                            </p>
                        </div>
                        
                        <div class="text-center">
                            <div class="bg-cue-green text-white w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-star text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Excellence</h3>
                            <p class="text-gray-600">
                                We strive for excellence in tournament organization, player development, 
                                and service delivery to all our stakeholders.
                            </p>
                        </div>
                        
                        <div class="text-center">
                            <div class="bg-cue-green text-white w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-seedling text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Growth</h3>
                            <p class="text-gray-600">
                                We are committed to continuous improvement and innovation in developing 
                                Kenya's pool sports ecosystem.
                            </p>
                        </div>
                        
                        <div class="text-center">
                            <div class="bg-cue-green text-white w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-balance-scale text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Fairness</h3>
                            <p class="text-gray-600">
                                Every player deserves equal opportunity to compete, develop their skills, 
                                and pursue their pool career aspirations.
                            </p>
                        </div>
                        
                        <div class="text-center">
                            <div class="bg-cue-green text-white w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-lightbulb text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Innovation</h3>
                            <p class="text-gray-600">
                                We embrace technology and creative solutions to enhance the pool sports 
                                experience for players and organizers alike.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Our Impact -->
                <div class="mb-20">
                    <div class="text-center mb-12">
                        <h2 class="text-4xl font-bold text-gray-900 mb-6">Our Impact</h2>
                        <p class="text-xl text-gray-600">
                            Measuring our contribution to Kenya's pool sports development
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div class="text-center">
                            <div class="text-4xl font-bold text-cue-green mb-2">500+</div>
                            <div class="text-gray-600">Active Players</div>
                            <div class="text-sm text-gray-500 mt-1">Across all tournament levels</div>
                        </div>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-cue-green mb-2">50+</div>
                            <div class="text-gray-600">Partner Venues</div>
                            <div class="text-sm text-gray-500 mt-1">Pool halls nationwide</div>
                        </div>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-cue-green mb-2">200+</div>
                            <div class="text-gray-600">Tournaments Organized</div>
                            <div class="text-sm text-gray-500 mt-1">Since our inception</div>
                        </div>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-cue-green mb-2">47</div>
                            <div class="text-gray-600">Counties Reached</div>
                            <div class="text-sm text-gray-500 mt-1">Nationwide coverage</div>
                        </div>
                    </div>
                </div>

                <!-- Join Our Mission -->
                <div class="text-center bg-gray-50 rounded-2xl p-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">Join Our Mission</h2>
                    <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                        Whether you're a player looking to develop your career, a venue owner interested in 
                        hosting tournaments, or someone passionate about growing Kenya's pool culture, 
                        there's a place for you in our community.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="/contact" class="bg-cue-green text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-cue-green-light transition duration-300">
                            Get Involved
                        </a>
                        <a href="/features" class="border-2 border-cue-green text-cue-green px-8 py-4 rounded-lg text-lg font-semibold hover:bg-cue-green hover:text-white transition duration-300">
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-cue-green-dark text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div>
                    <h3 class="text-2xl font-bold mb-4">CueSports Kenya</h3>
                    <p class="text-gray-300 mb-6">
                        Kenya's premier pool tournament management platform. 
                        Connecting players nationwide through organized competition.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">
                            <i class="fab fa-linkedin text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-xl font-bold mb-6">Get the app</h4>
                    <div class="space-y-4">
                        <a href="#" class="bg-black text-white px-6 py-3 rounded-lg flex items-center hover:bg-gray-800 transition duration-300 w-fit">
                            <i class="fab fa-apple mr-3 text-xl"></i>
                            <div>
                                <div class="text-xs">Download on the</div>
                                <div class="text-lg font-semibold">App Store</div>
                            </div>
                        </a>
                        <a href="#" class="bg-black text-white px-6 py-3 rounded-lg flex items-center hover:bg-gray-800 transition duration-300 w-fit">
                            <i class="fab fa-google-play mr-3 text-xl"></i>
                            <div>
                                <div class="text-xs">Get it on</div>
                                <div class="text-lg font-semibold">Google Play</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-12 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex space-x-6 mb-4 md:mb-0">
                        <a href="/" class="text-gray-300 hover:text-white transition duration-300">Home</a>
                        <a href="/features" class="text-gray-300 hover:text-white transition duration-300">Features</a>
                        <a href="/about" class="text-gray-300 hover:text-white transition duration-300">About</a>
                        <a href="/contact" class="text-gray-300 hover:text-white transition duration-300">Contact</a>
                        <a href="/blog" class="text-gray-300 hover:text-white transition duration-300">Blog</a>
                    </div>
                    <p class="text-gray-400 text-sm">
                        © 2024 CueSports Kenya. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Navigation Script -->
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            const logo = document.getElementById('nav-logo');
            const navLinks = document.querySelectorAll('#navbar a[id^="nav-link"]');
            const blogBtn = document.getElementById('nav-blog');
            const mobileBtn = document.getElementById('mobile-menu-btn');
            
            if (window.scrollY > 50) {
                // Scrolled state - white background, dark text
                navbar.className = 'bg-white shadow-lg fixed w-full z-50 transition-all duration-300';
                logo.className = 'text-2xl font-bold text-cue-green transition-colors duration-300';
                navLinks.forEach(link => {
                    link.className = 'text-gray-700 hover:text-cue-green transition duration-300';
                });
                blogBtn.className = 'bg-cue-green text-white px-4 py-2 rounded-lg hover:bg-cue-green-light transition duration-300';
                if (mobileBtn) {
                    mobileBtn.className = 'text-gray-700 hover:text-cue-green';
                }
            } else {
                // Top state - green background, white text
                navbar.className = 'bg-cue-green-dark fixed w-full z-50 transition-all duration-300';
                logo.className = 'text-2xl font-bold text-white transition-colors duration-300';
                navLinks.forEach(link => {
                    link.className = 'text-white hover:text-green-300 transition duration-300';
                });
                blogBtn.className = 'bg-white text-cue-green px-4 py-2 rounded-lg hover:bg-gray-100 transition duration-300';
                if (mobileBtn) {
                    mobileBtn.className = 'text-white hover:text-green-300';
                }
            }
        });

        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
