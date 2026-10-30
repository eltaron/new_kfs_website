document.addEventListener("DOMContentLoaded", function () {
    // --- Hero Carousel Initialization ---
    const heroCarouselElement = document.getElementById("heroCarousel");
    if (heroCarouselElement) {
        new bootstrap.Carousel(heroCarouselElement, {
            interval: 6000,
            pause: "hover",
            wrap: true,
        });
    }

    // --- Hero Floating Elements Subtle Parallax ---
    const floatingShapes = document.querySelectorAll(".floating-shape");
    if (floatingShapes.length > 0) {
        document.querySelector(".premium-hero").addEventListener("mousemove", function (e) {
            var rect = this.getBoundingClientRect();
            var x = (e.clientX - rect.left) / rect.width - 0.5;
            var y = (e.clientY - rect.top) / rect.height - 0.5;
            floatingShapes.forEach(function (shape, i) {
                var speed = 10 + i * 5;
                shape.style.transform = "translate(" + (x * speed) + "px, " + (y * speed) + "px)";
            });
        });
    }

    // --- Initialize Events Slider (Swiper.js) (No Changes) ---
    var swiperEvents = new Swiper(".events-slider", {
        // تفعيل التدوير اللا نهائي
        loop: true,

        // تفعيل التقليب التلقائي
        autoplay: {
            delay: 3000, // مدة البقاء على الصورة (3 ثواني)
            disableOnInteraction: false, // استمرار التقليب حتى لو ضغط المستخدم على الأزرار
        },

        // سرعة الانتقال بين السلايدات
        speed: 1000,

        // إعدادات العرض (Responsive)
        slidesPerView: 1,
        spaceBetween: 20,

        // أزرار التنقل
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },

        // تقسيم الشاشة (عرض أكثر من كارت في الكمبيوتر)
        breakpoints: {
            640: {
                slidesPerView: 2,
            },
            1024: {
                slidesPerView: 3, // عرض 3 كروت في الشاشات الكبيرة
            },
        },
    });

    // إيقاف التقليب مؤقتاً عند مرور الماوس فوق الكارت
    const eventsSection = document.getElementById("events-section");
    if (eventsSection) {
        eventsSection.addEventListener("mouseenter", () => {
            swiperEvents.autoplay.stop();
        });
        eventsSection.addEventListener("mouseleave", () => {
            swiperEvents.autoplay.start();
        });
    }

    // ================== MANUAL COUNTER LOGIC (REPLACEMENT) ==================

    // The function that handles the animation for a single element
    function animateCounter(element, target, duration) {
        let start = null;
        let current = 0;

        const step = (timestamp) => {
            if (!start) start = timestamp;
            const progress = timestamp - start;

            // Calculate current value based on progress
            current = Math.min(
                Math.floor((target / duration) * progress),
                target,
            );

            // Format number with commas and update the text
            element.textContent = current.toLocaleString("en-US");

            // Continue animation until target is reached
            if (current < target) {
                window.requestAnimationFrame(step);
            } else {
                // Ensure the final number is exactly the target, formatted
                element.textContent = target.toLocaleString("en-US");
            }
        };

        // Start the animation
        window.requestAnimationFrame(step);
    }

    // Intersection Observer to trigger the animation
    const statsSection = document.querySelector(".stats-section");
    const startCounter = (entries, observer) => {
        const [entry] = entries;
        if (!entry.isIntersecting) return;

        const counters = document.querySelectorAll(".stat-number");
        counters.forEach((counter) => {
            const target = +counter.dataset.target; // Get target number
            animateCounter(counter, target, 2000); // Animate over 2000ms (2 seconds)
        });

        // Disconnect observer after animation starts
        observer.unobserve(statsSection);
    };

    const statsObserver = new IntersectionObserver(startCounter, {
        root: null,
        threshold: 0.4,
    });

    if (statsSection) {
        statsObserver.observe(statsSection);
    }
    var swiper = new Swiper(".tourism-slider", {
        centeredSlides: true,
        loop: true,
        autoplay: { delay: 3500 },
        navigation: {
            nextEl: ".next-tour",
            prevEl: ".prev-tour",
        },
        breakpoints: {
            768: { slidesPerView: 2.2 },
            1024: { slidesPerView: 1.2 }, // 3 مشاريع في السطر على الكمبيوتر بمسافات واسعة
        },
    });
    // --- Entrance Animation for Project Cards ---
    const projectCards = document.querySelectorAll(".project-card");
    const animateOnScroll = (entries, observer) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // Apply a staggered delay for a nicer effect
                entry.target.style.transition = `opacity 0.6s ease ${
                    index * 0.1
                }s, transform 0.6s ease ${index * 0.1}s`;
                entry.target.classList.add("is-visible");
                observer.unobserve(entry.target); // Animate only once
            }
        });
    };

    const projectsObserver = new IntersectionObserver(animateOnScroll, {
        threshold: 0.1,
    });

    projectCards.forEach((card) => {
        projectsObserver.observe(card);
    });
    // --- Interactive Apps List Functionality ---
    const appLinks = document.querySelectorAll(".app-link");
    const appDetailsItems = document.querySelectorAll(".app-details-item");

    appLinks.forEach((link) => {
        link.addEventListener("mouseover", function () {
            // Remove 'active' from all links and items first
            appLinks.forEach((l) => l.classList.remove("active"));
            appDetailsItems.forEach((item) => item.classList.remove("active"));

            // Add 'active' to the hovered link
            this.classList.add("active");

            // Add 'active' to the corresponding details item
            const targetId = this.dataset.target;
            const targetDetail = document.querySelector(targetId);
            if (targetDetail) {
                targetDetail.classList.add("active");
            }
        });
    });

    // Optional: Reset to first item when mouse leaves the list container
    const listContainer = document.querySelector(".apps-list");
    if (listContainer) {
        listContainer.addEventListener("mouseleave", function () {
            // Remove 'active' from all
            appLinks.forEach((l) => l.classList.remove("active"));
            appDetailsItems.forEach((item) => item.classList.remove("active"));

            // Reactivate the first link and item as default
            document.querySelector(".app-link").classList.add("active");
            document.querySelector(".app-details-item").classList.add("active");
        });
    }
    // 1. تحديد جميع عناصر التنقل (العناوين الجانبية)
    const tabItems = document.querySelectorAll(".tab-item");
    // 2. تحديد جميع مناطق المحتوى (التفاصيل)
    const tabDetails = document.querySelectorAll(".tab-detail");

    tabItems.forEach((item) => {
        item.addEventListener("click", (event) => {
            // منع الانتقال الافتراضي للصفحة (مثل الانتقال عبر #azhar)
            event.preventDefault();

            // الحصول على اسم التبويب المستهدف من خاصية data-tab
            const targetTab = item.getAttribute("data-tab");

            // --- الخطوة 1: تحديث قائمة التنقل (الـ Navigation) ---

            // إزالة فئة 'active' من كل عناصر التنقل
            tabItems.forEach((i) => i.classList.remove("active"));
            // إضافة فئة 'active' للعنصر الذي تم النقر عليه
            item.classList.add("active");

            // --- الخطوة 2: تحديث منطقة المحتوى (Details) ---

            // إخفاء جميع المحتويات بإزالة فئة 'active-detail'
            tabDetails.forEach((detail) => {
                detail.classList.remove("active-detail");
            });

            // إظهار المحتوى المستهدف
            // يتم بناء الـ ID المستهدف عن طريق إضافة '-content'
            const contentId = targetTab + "-content";
            const targetContent = document.getElementById(contentId);

            if (targetContent) {
                targetContent.classList.add("active-detail");
            }
        });
    });
    const categoryItems = document.querySelectorAll(".category-item");
    if (categoryItems.length > 0) {
        categoryItems.forEach((item) => {
            item.addEventListener("click", function (e) {
                e.preventDefault();
                // First, remove 'active' from all other items
                categoryItems.forEach((i) => i.classList.remove("active"));
                // Then, add 'active' to the clicked item
                this.classList.add("active");

                // --- FUTURE INTEGRATION ---
                // Here you would add the code to filter markers on a real map
                // For example: filterMarkers(this.dataset.category);
                console.log(
                    "Category clicked:",
                    this.querySelector(".category-text").textContent.trim(),
                );
            });
        });
    }
});
