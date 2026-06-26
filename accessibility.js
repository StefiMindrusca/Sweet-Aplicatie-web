var mainContent = document.getElementById("main-content");
if (mainContent) {
    mainContent.setAttribute("tabindex", "-1");
}

var dropdowns = document.querySelectorAll(".dropdown");

function closeAllDropdowns() {
    dropdowns.forEach(function(dropdown) {
        dropdown.classList.remove("open");
        var toggle = dropdown.querySelector(".dropdown-toggle, .profile-icon");
        if (toggle) {
            toggle.setAttribute("aria-expanded", "false");
        }
    });
}

dropdowns.forEach(function(dropdown) {
    var toggle = dropdown.querySelector(".dropdown-toggle, .profile-icon");
    var menu = dropdown.querySelector(".dropdown-menu");

    if (!toggle || !menu) return;

    toggle.addEventListener("keydown", function(event) {
        if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            var isOpen = dropdown.classList.contains("open");

            closeAllDropdowns();

            if (!isOpen) {
                dropdown.classList.add("open");
                toggle.setAttribute("aria-expanded", "true");

                var firstLink = menu.querySelector("a");
                if (firstLink) {
                    firstLink.focus();
                }
            }
        }

        if (event.key === "ArrowDown") {
            event.preventDefault();
            dropdown.classList.add("open");
            toggle.setAttribute("aria-expanded", "true");

            var firstLink = menu.querySelector("a");
            if (firstLink) {
                firstLink.focus();
            }
        }
    });

    menu.addEventListener("keydown", function(event) {
        var links = Array.from(menu.querySelectorAll("a"));
        var currentIndex = links.indexOf(document.activeElement);

        if (event.key === "ArrowDown") {
            event.preventDefault();
            var next = currentIndex + 1;
            if (next < links.length) {
                links[next].focus();
            }
        }

        if (event.key === "ArrowUp") {
            event.preventDefault();
            if (currentIndex === 0) {
                toggle.focus();
            } else {
                links[currentIndex - 1].focus();
            }
        }

        if (event.key === "Escape") {
            closeAllDropdowns();
            toggle.focus();
        }

        if (event.key === "Tab") {
            closeAllDropdowns();
        }
    });
});

document.addEventListener("click", function(event) {
    var clickedInsideDropdown = false;

    dropdowns.forEach(function(dropdown) {
        if (dropdown.contains(event.target)) {
            clickedInsideDropdown = true;
        }
    });

    if (!clickedInsideDropdown) {
        closeAllDropdowns();
    }
});


var confirmationModal = document.getElementById("boxConfirmationBg");

if (confirmationModal) {

    var closeBtn = confirmationModal.querySelector(".box-confirmation-close");
    if (closeBtn) {
        closeBtn.focus();
    }

    confirmationModal.addEventListener("keydown", function(event) {

        if (confirmationModal.style.display === "none") {
            return;
        }

        var focusable = Array.from(
            confirmationModal.querySelectorAll("a, button")
        );

        if (focusable.length === 0) return;

        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (event.key === "Tab") {
            if (event.shiftKey) {
                if (document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        }

        if (event.key === "Escape") {
            if (typeof closeBoxConfirmation === "function") {
                closeBoxConfirmation();
            }
        }
    });
}
