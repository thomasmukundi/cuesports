<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CueSports Africa - Pool Tournament Management</title>
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
                        <h1 id="nav-logo" class="text-2xl font-bold text-white transition-colors duration-300">CueSports Africa</h1>
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

    <!-- Hero Section -->
    <section id="home" class="bg-cue-green-dark min-h-screen flex items-center relative pt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="text-white">
                    <h1 class="text-5xl lg:text-6xl font-bold mb-6">
                        Africa's Premier<br>
                        <span class="text-green-400">Pool Tournament</span><br>
                        Management System
                    </h1>
                    <p class="text-xl mb-8 text-gray-300">
                        Join Africa's comprehensive pool tournament ecosystem. Compete with players across the continent, 
                        develop your career, and be part of a thriving pool culture across Africa.
                    </p>
                    <div class="mb-8">
                        <p class="text-lg mb-4">Get the app</p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="#" class="bg-black text-white px-6 py-3 rounded-lg flex items-center justify-center hover:bg-gray-800 transition duration-300">
                                <i class="fab fa-apple mr-3 text-xl"></i>
                                <div>
                                    <div class="text-xs">Download on the</div>
                                    <div class="text-lg font-semibold">App Store</div>
                                </div>
                            </a>
                            <a href="#" class="bg-black text-white px-6 py-3 rounded-lg flex items-center justify-center hover:bg-gray-800 transition duration-300">
                                <i class="fab fa-google-play mr-3 text-xl"></i>
                                <div>
                                    <div class="text-xs">Get it on</div>
                                    <div class="text-lg font-semibold">Google Play</div>
                                </div>
                            </a>
                            <a href="https://www.seroxideentertainment.co.ke/tickets/spotbot/download.php" class="md:hidden bg-green-600 text-white px-6 py-3 rounded-lg flex items-center justify-center hover:bg-green-700 transition duration-300">
                                <i class="fas fa-download mr-3 text-xl"></i>
                                <div>
                                    <div class="text-xs">Download</div>
                                    <div class="text-lg font-semibold">Directly</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="relative z-20 hidden md:block">
                    <img src="https://themewagon.github.io/NextJS-Tailwind-App-Presentation-Page/image/iphones.png" 
                         alt="CueSports Africa Mobile App" 
                         class="w-full max-w-lg mx-auto">
                </div>
            </div>
        </div>
        
        <!-- Tournament Management Card positioned absolutely to overlay -->
        <div class="absolute bottom-0 left-0 right-0 transform translate-y-1/2 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-2xl p-6 lg:p-8 w-full max-w-6xl">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4 text-left">Tournament Management</h2>
                    <p class="text-lg text-gray-600 text-left leading-relaxed">
                        Experience seamless tournament organization with our comprehensive management system. 
                        From community level to national championships, we've got you covered. Our ecosystem offers 
                        structured career pathways, professional development opportunities, smart player pairing, real-time match updates, 
                        and comprehensive performance tracking to help build Africa's thriving pool culture and create sustainable careers for talented players.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- White section to accommodate the bottom half of the card -->
    <section class="pt-32 pb-16 bg-white"></section>

    <!-- Video Section - Hidden on mobile -->
    <section class="py-20 bg-white hidden md:block">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-black rounded-2xl overflow-hidden relative" style="padding-bottom: 35%; height: 0;">
                <div class="absolute inset-0 flex items-center justify-center">
                    <button class="bg-white rounded-full w-20 h-20 flex items-center justify-center hover:scale-110 transition duration-300">
                        <i class="fas fa-play text-cue-green text-2xl ml-1"></i>
                    </button>
                </div>
                <div class="absolute bottom-4 right-4 bg-cue-green text-white px-3 py-1 rounded">
                    Watch Demo
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <p class="text-cue-green font-semibold mb-2">POOL MANAGEMENT ECOSYSTEM</p>
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Comprehensive Career Development System</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Everything you need to build a career in pool sports across Africa. 
                    From community tournaments to professional opportunities, we provide the complete ecosystem for player development.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="bg-cue-green text-white w-16 h-16 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-trophy text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Career Development</h3>
                    <p class="text-gray-600">
                        Build a sustainable career through structured tournaments at community, county, regional, and national levels. 
                        Progress through our professional development pathways.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="bg-cue-green text-white w-16 h-16 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Community Building</h3>
                    <p class="text-gray-600">
                        Connect with pool players across the continent and build lasting relationships within Africa's 
                        growing pool community and culture.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="bg-cue-green text-white w-16 h-16 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Professional Standards</h3>
                    <p class="text-gray-600">
                        Experience world-class tournament management with standardized rules, 
                        transparent rankings, and professional integrity across all competitions.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="bg-cue-green text-white w-16 h-16 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-medal text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Career Opportunities</h3>
                    <p class="text-gray-600">
                        Access coaching programs, sponsorship opportunities, and multiple career paths 
                        within Africa's expanding pool sports ecosystem.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Mobile Stats Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="order-2 lg:order-1 hidden md:block">
                    <img src="https://themewagon.github.io/NextJS-Tailwind-App-Presentation-Page/image/iphone.png" 
                         alt="Mobile App Stats" 
                         class="w-full max-w-md mx-auto">
                </div>
                <div class="order-1 lg:order-2">
                    <h2 class="text-4xl font-bold text-gray-900 mb-6">Mobile Excellence</h2>
                    <p class="text-xl text-gray-600 mb-8">
                        Access your tournament data on the go. Whether you're at the pool hall or planning your next match, 
                        our mobile app keeps you connected.
                    </p>
                    
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <div class="text-4xl font-bold text-cue-green mb-2">500+</div>
                            <div class="text-gray-600">Active Players</div>
                        </div>
                        <div>
                            <div class="text-4xl font-bold text-cue-green mb-2">50+</div>
                            <div class="text-gray-600">Tournament Venues</div>
                        </div>
                        <div>
                            <div class="text-4xl font-bold text-cue-green mb-2">24/7</div>
                            <div class="text-gray-600">Support</div>
                        </div>
                        <div>
                            <div class="text-4xl font-bold text-cue-green mb-2">5/5</div>
                            <div class="text-gray-600">User Rating</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <div class="bg-cue-green text-white w-16 h-16 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-quote-left text-2xl"></i>
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-4">What Players Say</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Discover what our valued players have to say about their experiences with 
                    CueSports Africa. We take pride in delivering exceptional tournament management.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <img src="https://themewagon.github.io/NextJS-Tailwind-App-Presentation-Page/image/avatar1.jpg" 
                         alt="Player 1" 
                         class="w-20 h-20 rounded-full mx-auto mb-4 object-cover">
                    <h3 class="text-xl font-bold text-gray-900 mb-1">James Mwangi</h3>
                    <p class="text-cue-green mb-4">CHAMPION @ NAIROBI COUNTY</p>
                    <p class="text-gray-600">
                        "This app has revolutionized how I participate in pool tournaments. 
                        The organization is top-notch and the competition is fierce!"
                    </p>
                </div>
                
                <div class="text-center">
                    <img src="https://themewagon.github.io/NextJS-Tailwind-App-Presentation-Page/image/avatar2.jpg" 
                         alt="Player 2" 
                         class="w-20 h-20 rounded-full mx-auto mb-4 object-cover">
                    <h3 class="text-xl font-bold text-gray-900 mb-1">Grace Wanjiku</h3>
                    <p class="text-cue-green mb-4">REGIONAL FINALIST @ CENTRAL REGION</p>
                    <p class="text-gray-600">
                        "I love how easy it is to find matches and track my progress. 
                        The community aspect makes every tournament feel special."
                    </p>
                </div>
                
                <div class="text-center">
                    <img src="https://themewagon.github.io/NextJS-Tailwind-App-Presentation-Page/image/avatar3.jpg" 
                         alt="Player 3" 
                         class="w-20 h-20 rounded-full mx-auto mb-4 object-cover">
                    <h3 class="text-xl font-bold text-gray-900 mb-1">David Kipchoge</h3>
                    <p class="text-cue-green mb-4">TOURNAMENT ORGANIZER @ RIFT VALLEY</p>
                    <p class="text-gray-600">
                        "As an organizer, this platform has made managing tournaments incredibly efficient. 
                        Players love the transparency and real-time updates."
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
                <p class="text-xl text-gray-600">
                    Get answers to common questions about participating in CueSports Africa tournaments.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">How do I get started?</h3>
                    <p class="text-gray-600 mb-8">
                        Getting started is easy! Simply download our mobile app, create an account, 
                        select your location, and you'll be able to join tournaments in your area.
                    </p>
                    
                    <h3 class="text-xl font-bold text-gray-900 mb-3">How are matches scheduled?</h3>
                    <p class="text-gray-600">
                        Once you're paired with an opponent, you can coordinate match dates and times 
                        through our in-app messaging system. Venues are typically local pool halls in your area.
                    </p>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Is there a registration fee?</h3>
                    <p class="text-gray-600 mb-8">
                        Tournament registration fees vary by level and location. Community tournaments 
                        typically have minimal fees, while regional and national tournaments may have higher entry fees.
                    </p>
                    
                    <h3 class="text-xl font-bold text-gray-900 mb-3">What if I need help or have technical issues?</h3>
                    <p class="text-gray-600">
                        Our dedicated support team is here to assist you. Reach out via the app's support 
                        section, email, or phone, and we'll get back to you promptly.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-cue-green-dark text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div>
                    <h3 class="text-2xl font-bold mb-4">CueSports Africa</h3>
                    <p class="text-gray-300 mb-6">
                        Africa's premier pool tournament management platform. 
                        Connecting players across the continent through organized competition.
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
                        <a href="https://www.seroxideentertainment.co.ke/tickets/spotbot/download.php" class="md:hidden bg-green-600 text-white px-6 py-3 rounded-lg flex items-center hover:bg-green-700 transition duration-300 w-fit">
                            <i class="fas fa-download mr-3 text-xl"></i>
                            <div>
                                <div class="text-xs">Download</div>
                                <div class="text-lg font-semibold">Directly</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-12 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex space-x-6 mb-4 md:mb-0">
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">About Us</a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">Privacy Policy</a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">Terms of Service</a>
                        <a href="#" class="text-gray-300 hover:text-white transition duration-300">Contact</a>
                    </div>
                    <p class="text-gray-400 text-sm">
                        © 2025 CueSports Africa. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Navigation and Smooth Scrolling Script -->
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

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
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
