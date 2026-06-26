console.log("Sweet a pornit corect.");

function increaseQuantity() {
    let quantityInput = document.getElementById("quantity");
    let currentValue = parseInt(quantityInput.value);
    quantityInput.value = currentValue + 1;
}

function decreaseQuantity() {
    let quantityInput = document.getElementById("quantity");
    let currentValue = parseInt(quantityInput.value);

    if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
    }
}
console.log("Sweet a pornit corect.");

const sizeButtons = document.querySelectorAll('.box-size-btn');
const productCards = document.querySelectorAll('.box-product-card');

const boxImage = document.getElementById('boxBaseImage');
const boxLayer = document.getElementById('boxItemsLayer');
const placeholder = document.getElementById('boxPlaceholderText');

const selectedCount = document.getElementById('selectedCount');
const maxCount = document.getElementById('maxCount');
const totalPrice = document.getElementById('totalPrice');

const boxSizeInput = document.getElementById('boxSizeInput');
const boxItemsInput = document.getElementById('boxItemsInput');
const submitBtn = document.getElementById('boxSubmitBtn');
const sizeSelector = document.getElementById('boxSizeSelector');

if (sizeButtons.length > 0 && boxImage && boxLayer) {
    let currentSize = 4;
    let cart = {};

    const layouts = {
        4: [
            { top: '51%', left: '18%', width: '18%' },
            { top: '51%', left: '39%', width: '18%' },
            { top: '51%', left: '60%', width: '18%' },
            { top: '51%', left: '80%', width: '18%' }
        ],

        6: [
            { top: '46%', left: '25%', width: '18%' },
            { top: '46%', left: '50%', width: '18%' },
            { top: '46%', left: '75%', width: '18%' },

            { top: '71%', left: '25%', width: '18%' },
            { top: '71%', left: '50%', width: '18%' },
            { top: '71%', left: '75%', width: '18%' }
        ],

        12: [
            { top: '42%', left: '23%', width: '18%' },
            { top: '42%', left: '40%', width: '18%' },
            { top: '42%', left: '57%', width: '18%' },
            { top: '42%', left: '74%', width: '18%' },

            { top: '59%', left: '23%', width: '18%' },
            { top: '59%', left: '40%', width: '18%' },
            { top: '59%', left: '57%', width: '18%' },
            { top: '59%', left: '74%', width: '18%' },

            { top: '76%', left: '23%', width: '18%' },
            { top: '76%', left: '40%', width: '18%' },
            { top: '76%', left: '57%', width: '18%' },
            { top: '76%', left: '74%', width: '18%' }
        ]
    };

    function totalSelected() {
        let total = 0;

        for (let id in cart) {
            total += cart[id].quantity;
        }

        return total;
    }

    function totalSum() {
        let total = 0;

        for (let id in cart) {
            total += cart[id].price * cart[id].quantity;
        }

        return total;
    }

    function renderPreview() {
        boxLayer.innerHTML = '';

        let selectedImages = [];

        for (let id in cart) {
            for (let i = 0; i < cart[id].quantity; i++) {
                selectedImages.push({
                    image: cart[id].image,
                    name: cart[id].name
                });
            }
        }

        if (selectedImages.length === 0) {
            placeholder.style.display = 'block';
            placeholder.textContent = 'Alege ' + currentSize + ' produse pentru a completa cutia.';
        } else {
            placeholder.style.display = 'none';
        }

        selectedImages.forEach(function(item, index) {
            if (!layouts[currentSize][index]) return;
            if (item.image === '') return;

            const img = document.createElement('img');
            img.src = item.image;
            img.alt = item.name;
            img.className = 'box-item-preview';

            img.style.top = layouts[currentSize][index].top;
            img.style.left = layouts[currentSize][index].left;
            img.style.width = layouts[currentSize][index].width;
            img.style.transform = 'translate(-50%, -50%)';

            boxLayer.appendChild(img);
        });
    }

    function updateSummary() {
        selectedCount.textContent = totalSelected();
        maxCount.textContent = currentSize;
        totalPrice.textContent = totalSum().toFixed(2);

        submitBtn.disabled = totalSelected() !== currentSize;

        productCards.forEach(function(card) {
            const cardId = card.dataset.id;
            const cardStock = parseInt(card.dataset.stock) || 0;
            const cardInCart = parseInt(card.dataset.inCart) || 0;
            const stocDisponibil = cardStock - cardInCart;
            const plusBtn = card.querySelector('.plus-btn');

            if (!plusBtn) return;

            const cantitateCurenta = cart[cardId] ? cart[cardId].quantity : 0;

            if (stocDisponibil <= 0) {
                plusBtn.disabled = true;
            } else if (cantitateCurenta >= stocDisponibil) {
                plusBtn.disabled = true;
            } else if (totalSelected() >= currentSize) {
                plusBtn.disabled = true;
            } else {
                plusBtn.disabled = false;
            }
        });

        boxItemsInput.value = JSON.stringify({
            size: currentSize,
            items: cart
        });
    }

    function resetBox() {
        cart = {};

        document.querySelectorAll('.qty-value').forEach(function(item) {
            item.textContent = '0';
        });

        renderPreview();
        updateSummary();
    }

    function activateSizeButton(button) {
        sizeButtons.forEach(function(btn) {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
        });

        button.classList.add('active');
        button.setAttribute('aria-pressed', 'true');

        currentSize = parseInt(button.dataset.size);
        boxImage.src = button.dataset.image;
        boxSizeInput.value = currentSize;

        resetBox();
    }

    sizeButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            activateSizeButton(this);
        });
    });

    if (sizeSelector) {
        sizeSelector.addEventListener('keydown', function(event) {
            const currentIndex = Array.from(sizeButtons).findIndex(function(button) {
                return button.classList.contains('active');
            });

            let newIndex = currentIndex;

            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                newIndex = currentIndex + 1;
                if (newIndex >= sizeButtons.length) {
                    newIndex = 0;
                }
                event.preventDefault();
                sizeButtons[newIndex].focus();
                activateSizeButton(sizeButtons[newIndex]);
            }

            if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                newIndex = currentIndex - 1;
                if (newIndex < 0) {
                    newIndex = sizeButtons.length - 1;
                }
                event.preventDefault();
                sizeButtons[newIndex].focus();
                activateSizeButton(sizeButtons[newIndex]);
            }
        });
    }

    productCards.forEach(function(card) {
        const id = card.dataset.id;
        const name = card.dataset.name;
        const price = parseFloat(card.dataset.price);
        const stock = parseInt(card.dataset.stock) || 0;
        const inCart = parseInt(card.dataset.inCart) || 0;
        const stocDisponibil = stock - inCart;
        const image = card.dataset.image;

        const minusBtn = card.querySelector('.minus-btn');
        const plusBtn = card.querySelector('.plus-btn');
        const qty = card.querySelector('.qty-value');

        function addProduct() {
            if (totalSelected() >= currentSize) return;

            const cantitateCurenta = cart[id] ? cart[id].quantity : 0;
            if (cantitateCurenta >= stocDisponibil) return;

            if (!cart[id]) {
                cart[id] = {
                    id: id,
                    name: name,
                    price: price,
                    image: image,
                    quantity: 0
                };
            }

            cart[id].quantity++;
            qty.textContent = cart[id].quantity;

            renderPreview();
            updateSummary();
        }

        function removeProduct() {
            if (!cart[id]) return;

            cart[id].quantity--;

            if (cart[id].quantity <= 0) {
                delete cart[id];
                qty.textContent = '0';
            } else {
                qty.textContent = cart[id].quantity;
            }

            renderPreview();
            updateSummary();
        }

        plusBtn.addEventListener('click', addProduct);
        minusBtn.addEventListener('click', removeProduct);

        plusBtn.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                addProduct();
            }
        });

        minusBtn.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                removeProduct();
            }
        });
    });

    renderPreview();
    updateSummary();
}

function increaseQuantity() {
    let quantityInput = document.getElementById("quantity");

    if (!quantityInput) return;

    let currentValue = parseInt(quantityInput.value);
    quantityInput.value = currentValue + 1;
}

function decreaseQuantity() {
    let quantityInput = document.getElementById("quantity");

    if (!quantityInput) return;

    let currentValue = parseInt(quantityInput.value);

    if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
    }
}

const infoButtons = document.querySelectorAll(".box-info-btn");
const infoBg = document.getElementById("boxInfoBg");
const infoClose = document.getElementById("boxInfoClose");
const infoImage = document.getElementById("boxInfoImage");
const infoTitle = document.getElementById("boxInfoTitle");
const infoDescription = document.getElementById("boxInfoDescription");
const infoIngredients = document.getElementById("boxInfoIngredients");
const infoPrice = document.getElementById("boxInfoPrice");
const infoStele = document.getElementById("boxInfoStele");

let lastFocusedInfoButton = null;

infoButtons.forEach(function (button) {
    button.addEventListener("click", function () {
        const card = button.closest(".box-product-card");

        lastFocusedInfoButton = button;

        infoTitle.textContent = card.dataset.name || "";
        infoDescription.textContent = card.dataset.description || "Nu există descriere.";
        infoIngredients.textContent = card.dataset.ingredients || "Nu există ingrediente.";
        infoPrice.textContent = Number(card.dataset.price || 0).toFixed(2) + " lei";

        const rating = parseInt(card.dataset.rating || 0);
        const ratingTotal = parseInt(card.dataset.ratingTotal || 0);
        if (infoStele) {
            if (rating > 0) {
                let stele = "";
                for (let i = 1; i <= 5; i++) {
                    stele += '<span class="' + (i <= rating ? "stea-plina" : "stea-goala") + '">★</span>';
                }
                stele += '<span class="stele-total">(' + ratingTotal + ')</span>';
                infoStele.innerHTML = stele;
                infoStele.style.display = "flex";
            } else {
                infoStele.innerHTML = "";
                infoStele.style.display = "none";
            }
        }

        if (card.dataset.image) {
            infoImage.src = card.dataset.image;
            infoImage.alt = card.dataset.name || "Imagine produs";
            infoImage.style.display = "block";
        } else {
            infoImage.removeAttribute("src");
            infoImage.alt = "";
            infoImage.style.display = "none";
        }

        infoBg.hidden = false;
        infoClose.focus();
    });
});

function closeBoxInfo() {
    infoBg.hidden = true;

    if (lastFocusedInfoButton) {
        lastFocusedInfoButton.focus();
    }
}

if (infoClose) {
    infoClose.addEventListener("click", closeBoxInfo);
}

if (infoBg) {
    infoBg.addEventListener("click", function (event) {
        if (event.target === infoBg) {
            closeBoxInfo();
        }
    });
}

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && infoBg && !infoBg.hidden) {
        closeBoxInfo();
    }
});
