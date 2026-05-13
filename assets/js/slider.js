let slideIndex = 0;

document.addEventListener("DOMContentLoaded", function () {
    const slides = document.querySelectorAll(".slide");

    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove("active"));
        slides[index].classList.add("active");
    }

    function moveSlide(step) {
        slideIndex += step;
        if (slideIndex >= slides.length) slideIndex = 0;
        if (slideIndex < 0) slideIndex = slides.length - 1;
        showSlide(slideIndex);
    }

    window.moveSlide = moveSlide;

    if (slides.length > 0) {
        showSlide(slideIndex);
        setInterval(function () {
            moveSlide(1);
        }, 5000);
    }
});
