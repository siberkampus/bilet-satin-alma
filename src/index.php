<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="static/css/homepage.css">
    <link rel="stylesheet" href="static/js/homepage.js">
</head>

<body>


    <nav class="navbar">
        <a class="navbar-brand py-lg-2 mb-lg-5 px-lg-6 me-0" href="/">
            <img src="static/images/logo.png" alt="Logo" style="height: 60px; width: auto; object-fit: contain; filter: brightness(1.1);">
        </a>
        <div class="nav-links">
            <a href="/index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Anasayfa</a>
            <a href="/detail.php" class="<?= basename($_SERVER['PHP_SELF']) === 'detail.php' ? 'active' : '' ?>">Sefer Detayları</a>
            <?php if (isset($_SESSION["login"]) && $_SESSION["login"] === true && $_SESSION["role"] === "admin"): ?>
                <a href="/admin_panel.php">Admin Panel </a>
            <?php endif; ?>
            <?php if (isset($_SESSION["login"]) && $_SESSION["login"] === true && $_SESSION["role"] === "company"): ?>
                <a href="/company_panel.php">Company Panel </a>
            <?php endif; ?>
            <?php if (isset($_SESSION["login"]) && $_SESSION["login"] === true && $_SESSION["role"] === "user"): ?>
                <a href="/user_profile.php">Profilim </a>
            <?php endif; ?>
            <?php if (isset($_SESSION["login"]) && $_SESSION["login"] === true): ?>
                <a href="/logout.php">Çıkış Yap </a>
            <?php else: ?>
                <a href="/login.php">Giriş Yap</a>
                <a href="/register.php">Kayıt Ol</a>
            <?php endif; ?>
        </div>




        <button class="mobile-menu-btn">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </nav>

    <div class="mobile-menu">
        <a href="/index.php" class="active">Anasayfa</a>
        <a href="/detail.php">Sefer Detayları</a>
        <?php
        session_start();
        if (isset($_SESSION["login"]) && $_SESSION["login"] === true): ?>
            <a href="/logout.php">Çıkış Yap</a>
        <?php else: ?>
            <a href="/login.php">Giriş Yap</a>
            <a href="/register.php">Kayıt Ol</a>
        <?php endif; ?>
    </div>


    <main class="hero">
        <div class="hero-content">
            <h1>✨ Türkiye'nin
                Popüler Seyahat Uygulaması
                <span class="thumbnail">
                    <img src="static/images/otobus.jpg" alt="Modern interior thumbnail">
                </span>
            </h1>
            <div class="cta-container">
                <button class="booking-btn">
                    Şimdi Yolculuğa Çıkın </button>
            </div>
        </div>

        <div class="hero-image">
            <div class="circular-image">
                <img src="static/images/otobus2.jpg" alt="Modern interior">
            </div>
        </div>
    </main>
    <section class="difference-section">
        <div class="features-header">
            <span class="features-label">Neden Farklıyız</span>
            <h2 class="features-title">
                <span class="decorative-dash">~</span>
                Bizi Farklı Yapan
                <span class="decorative-dash">~</span>
            </h2>
        </div>

        <div class="carousel">
            <div class="carousel-track">
                <div class="carousel-item">
                    <div class="circle orange">
                        <div class="icon wavy"></div>
                    </div>
                    <div class="description">
                        <h2>Konforlu Yolculuk, Güvenli Adres!</h2>
                        <p>AnadoluBilet ile Türkiye’nin dört bir yanına güvenli, rahat ve zamanında yolculuk yapın. Sizin için her detay düşünüldü.</p>
                    </div>
                    <div class="circle yellow">
                        <svg viewBox="0 0 24 24" class="icon">
                            <path d="M12 3l4 7h-8l4-7z"></path>
                            <path d="M12 21l-4-7h8l-4 7z"></path>
                        </svg>
                    </div>
                </div>

                <div class="carousel-item">
                    <div class="circle yellow">
                        <div class="icon star"></div>
                    </div>
                    <div class="description">
                        <h2>Her Yolculukta Mükemmel Deneyim</h2>
                        <p>Uygun fiyat, kolay biletleme ve 7/24 destek ile seyahatin keyfini çıkarın. AnadoluBilet her adımda yanınızda!</p>
                    </div>
                    <div class="circle pink">
                        <div class="feature-icon pink">
                            <svg viewBox="0 0 24 24" class="icon">
                                <path d="M3 6c2 0 4 2 6 2s4-2 6-2 4 2 6 2"></path>
                                <path d="M3 12c2 0 4 2 6 2s4-2 6-2 4 2 6 2"></path>
                                <path d="M3 18c2 0 4 2 6 2s4-2 6-2 4 2 6 2"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="carousel-item">
                    <div class="circle orange">
                        <div class="icon wavy"></div>
                    </div>
                    <div class="description">
                        <h2>Tek Tıkla Bilet, Anında Yola Çık!</h2>
                        <p>Modern arayüzümüz sayesinde birkaç saniyede otobüs biletinizi alın, koltuğunuzu seçin ve yolculuğa hazır olun!</p>
                    </div>
                    <div class="circle yellow">
                        <svg viewBox="0 0 24 24" class="icon">
                            <path d="M12 3l4 7h-8l4-7z"></path>
                            <path d="M12 21l-4-7h8l-4 7z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="navigation">
            <button class="nav-btn prev">&larr;</button>
            <button class="nav-btn next">&rarr;</button>
        </div>
    </section>

    <section class="constr-services">
        <div class="features-header">
            <span class="features-label">BİLMENİZ GEREKENLER</span>
            <h2 class="features-title">
                <span class="decorative-dash">~</span>
                Hizmetlerimiz
                <span class="decorative-dash">~</span>
            </h2>
        </div>

        <div class="constr-grid">
            <div class="constr-card">
                <div class="constr-blob constr-blob-1"></div>
                <div class="constr-content">
                    <h3>Online Bilet Satışı</h3>
                    <p>Yolcular biletlerini her yerden ve her zaman kolay ve güvenli bir şekilde online olarak alabilirler.</p>
                </div>
            </div>

            <div class="constr-card">
                <div class="constr-blob constr-blob-2"></div>
                <div class="constr-content">
                    <h3>Kurumsal Seyahat</h3>
                    <p>Şirketler ve organizasyonlar için sorunsuz grup seyahat çözümleri sunuyoruz.</p>
                </div>
            </div>

            <div class="constr-card">
                <div class="constr-blob constr-blob-3"></div>
                <div class="constr-content">
                    <h3>Müşteri Desteği</h3>
                    <p>Her türlü seyahat sorusu, bilet değişiklikleri veya yolculuk sırasında destek için 7/24 hizmetinizdeyiz.</p>
                </div>
            </div>
        </div>
    </section>

    >

    <section class="info-section">
        <div class="info-header">
            <div class="background-shape"></div>
            <h2>Firma Hakkında</h2>
            <p>Güvenilir, güvenli ve konforlu seyahati herkes için sağlıyoruz.</p>
        </div>
        <div class="info-stats">
            <div class="stat">
                <h3 class="stat-number" data-target="150">110</h3>
                <p>Günlük Rota</p>
            </div>
            <div class="stat">
                <h3 class="stat-number" data-target="35">30</h3>
                <p>Hedef Nokta</p>
            </div>
            <div class="stat">
                <h3 class="stat-number" data-target="12000">2000</h3>
                <p>Mutlu Yolcu</p>
            </div>
        </div>
    </section>



    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const track = document.querySelector(".carousel-track");
            const items = Array.from(track.children);
            let currentIndex = 0;
            const slideInterval = 4000; 

            const itemWidth = items[0].getBoundingClientRect().width;

            items.forEach((item, index) => {
                item.style.left = `${itemWidth * index}px`;
            });

            function moveToSlide(index) {
                track.style.transform = `translateX(-${itemWidth * index}px)`;
                track.style.transition = "transform 0.8s ease-in-out";
                currentIndex = index;
            }

            function nextSlide() {
                const nextIndex = (currentIndex + 1) % items.length;
                moveToSlide(nextIndex);
            }

            setInterval(nextSlide, slideInterval);

            window.addEventListener("resize", () => {
                const newWidth = items[0].getBoundingClientRect().width;
                items.forEach((item, index) => {
                    item.style.left = `${newWidth * index}px`;
                });
                moveToSlide(currentIndex);
            });
        });
    </script>

</body>

</html>