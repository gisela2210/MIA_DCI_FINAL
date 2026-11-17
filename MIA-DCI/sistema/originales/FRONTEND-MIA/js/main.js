const rootElement = document.documentElement;
const contrastButtons = document.querySelectorAll(".brand-contrast-action, .brand-text-action[aria-label='Contraste alto']");
const increaseTextButtons = document.querySelectorAll('.brand-text-action[aria-label="Aumentar texto"]');
const decreaseTextButtons = document.querySelectorAll('.brand-text-action[aria-label="Reducir texto"]');
const resetTextButtons = document.querySelectorAll('.brand-text-action[aria-label="Texto normal"]');
const translateButtons = document.querySelectorAll("[data-translate-lang]");
const storedContrast = localStorage.getItem("mia-high-contrast") === "true";
const textScaleSelector = "p, li, dd, dt, a, h1, h2, h3, h4, .form-control, .btn, figcaption, blockquote";
const textMarginSelector = "p, h2, h3, h4";
const textScaleElements = () => Array.from(document.body.querySelectorAll(textScaleSelector));
const paragraphFontSize = () => {
  const paragraph = document.body.querySelector("p") || document.body;
  return parseInt(window.getComputedStyle(paragraph).fontSize, 10);
};

const changeElementFontSize = (element, delta) => {
  const currentFontSize = parseInt(window.getComputedStyle(element).fontSize, 10);
  element.style.fontSize = `${currentFontSize + delta}px`;

  if (element.matches(textMarginSelector)) {
    const currentMargin = parseInt(window.getComputedStyle(element).marginBottom, 10);
    element.style.marginBottom = `${Math.max(0, currentMargin + (delta * 2))}px`;
  }
};

const increaseTecnmText = (event) => {
  event?.preventDefault();

  if (paragraphFontSize() + 1 <= 26) {
    textScaleElements().forEach((element) => changeElementFontSize(element, 1));
  }
};

const decreaseTecnmText = (event) => {
  event?.preventDefault();

  if (paragraphFontSize() - 1 >= 11) {
    textScaleElements().forEach((element) => changeElementFontSize(element, -1));
  }
};

const resetTecnmText = (event) => {
  event?.preventDefault();
  textScaleElements().forEach((element) => {
    element.style.fontSize = "";

    if (element.matches(textMarginSelector)) {
      element.style.marginBottom = "";
    }
  });
};

const applyContrast = (enabled) => {
  rootElement.classList.toggle("is-high-contrast", enabled);
  contrastButtons.forEach((button) => button.setAttribute("aria-pressed", String(enabled)));
  localStorage.setItem("mia-high-contrast", String(enabled));
};

const setTranslateCookie = (language) => {
  const value = language === "es" ? "/es/es" : `/es/${language}`;
  const expires = "expires=Fri, 31 Dec 9999 23:59:59 GMT";
  document.cookie = `googtrans=${value}; ${expires}; path=/`;
  if (location.hostname.includes(".")) {
    document.cookie = `googtrans=${value}; ${expires}; path=/; domain=${location.hostname}`;
  }
};

window.googleTranslateElementInit = () => {
  if (!window.google?.translate?.TranslateElement) {
    return;
  }

  new window.google.translate.TranslateElement(
    { pageLanguage: "es", autoDisplay: false },
    "google_translate_element"
  );
};

const ensureTranslateElement = () => {
  if (!document.getElementById("google_translate_element")) {
    const mount = document.createElement("div");
    mount.id = "google_translate_element";
    mount.className = "google-translate-mount";
    mount.setAttribute("aria-hidden", "true");
    document.body.append(mount);
  }

  if (!document.querySelector('script[data-js="google-translate"]')) {
    const script = document.createElement("script");
    script.src = "https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit";
    script.async = true;
    script.dataset.js = "google-translate";
    document.head.append(script);
  }
};

applyContrast(storedContrast);
ensureTranslateElement();

contrastButtons.forEach((button) => {
  button.setAttribute("aria-pressed", String(storedContrast));
  button.addEventListener("click", () => {
    applyContrast(!rootElement.classList.contains("is-high-contrast"));
  });
});

increaseTextButtons.forEach((button) => button.addEventListener("click", increaseTecnmText));
decreaseTextButtons.forEach((button) => button.addEventListener("click", decreaseTecnmText));
resetTextButtons.forEach((button) => button.addEventListener("click", resetTecnmText));

translateButtons.forEach((button) => {
  button.addEventListener("click", () => {
    const language = button.dataset.translateLang || "es";
    setTranslateCookie(language);
    location.hash = language === "es" ? "googtrans(es|es)" : `googtrans(es|${language})`;
    location.reload();
  });
});
const brandStrip = document.querySelector(".brand-strip");
const siteHeader = document.querySelector(".site-header");

if (brandStrip && siteHeader) {
  const sentinel = document.createElement("span");
  sentinel.className = "project-header-sentinel";
  sentinel.setAttribute("aria-hidden", "true");
  brandStrip.before(sentinel);

  const spacer = document.createElement("span");
  spacer.className = "project-header-spacer";
  spacer.setAttribute("aria-hidden", "true");
  siteHeader.after(spacer);

  const setProjectHeaderState = (isFixed) => {
    document.body.classList.toggle("project-header-is-fixed", isFixed);
  };

  if ("IntersectionObserver" in window) {
    const projectHeaderObserver = new IntersectionObserver(
      ([entry]) => {
        setProjectHeaderState(!entry.isIntersecting);
      },
      { threshold: 0 }
    );

    projectHeaderObserver.observe(sentinel);
  } else {
    const updateProjectHeaderState = () => {
      const sentinelTop = sentinel.getBoundingClientRect().top;
      setProjectHeaderState(sentinelTop < 0);
    };

    window.addEventListener("scroll", updateProjectHeaderState, { passive: true });
    window.addEventListener("resize", updateProjectHeaderState);
    updateProjectHeaderState();
  }
}

const menuButtons = document.querySelectorAll('[data-js="menu-toggle"]');

menuButtons.forEach((button) => {
  const menuId = button.getAttribute("aria-controls");
  const menu = menuId ? document.getElementById(menuId) : null;

  if (!menu) {
    return;
  }

  const closeMenu = () => {
    button.setAttribute("aria-expanded", "false");
    menu.classList.remove("is-open");
  };

  button.addEventListener("click", () => {
    const isOpen = button.getAttribute("aria-expanded") === "true";

    button.setAttribute("aria-expanded", String(!isOpen));
    menu.classList.toggle("is-open", !isOpen);
  });

  menu.addEventListener("click", (event) => {
    if (event.target.closest("a")) {
      closeMenu();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeMenu();
      button.focus();
    }
  });
});

const submenuButtons = document.querySelectorAll('[data-js="submenu-toggle"]');

submenuButtons.forEach((button) => {
  const dropdown = button.closest('[data-js="nav-dropdown"]');

  if (!dropdown) {
    return;
  }

  button.addEventListener("click", (event) => {
    event.stopPropagation();

    const isOpen = button.getAttribute("aria-expanded") === "true";
    button.setAttribute("aria-expanded", String(!isOpen));
    dropdown.classList.toggle("is-open", !isOpen);
  });

  document.addEventListener("click", (event) => {
    if (!dropdown.contains(event.target)) {
      button.setAttribute("aria-expanded", "false");
      dropdown.classList.remove("is-open");
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      button.setAttribute("aria-expanded", "false");
      dropdown.classList.remove("is-open");
    }
  });
});

const announcementSliders = document.querySelectorAll('[data-js="announcements-slider"]');

announcementSliders.forEach((slider) => {
  const slides = Array.from(slider.querySelectorAll('[data-js="announcement-slide"]'));
  const prevButton = slider.querySelector('[data-js="announcement-prev"]');
  const nextButton = slider.querySelector('[data-js="announcement-next"]');
  const dotsContainer = slider.querySelector('[data-js="announcement-dots"]');

  if (slides.length === 0) {
    return;
  }

  let currentIndex = Math.max(0, slides.findIndex((slide) => slide.classList.contains("is-active")));
  let autoplayId = null;
  const dots = [];

  const setActiveSlide = (nextIndex) => {
    currentIndex = (nextIndex + slides.length) % slides.length;

    slides.forEach((slide, index) => {
      const isActive = index === currentIndex;
      slide.classList.toggle("is-active", isActive);
      slide.setAttribute("aria-hidden", String(!isActive));
    });

    dots.forEach((dot, index) => {
      const isActive = index === currentIndex;
      dot.setAttribute("aria-current", String(isActive));
    });
  };

  const stopAutoplay = () => {
    if (autoplayId) {
      window.clearInterval(autoplayId);
      autoplayId = null;
    }
  };

  const startAutoplay = () => {
    stopAutoplay();

    if (slides.length > 1) {
      autoplayId = window.setInterval(() => setActiveSlide(currentIndex + 1), 6000);
    }
  };

  slides.forEach((_, index) => {
    const dot = document.createElement("button");
    dot.type = "button";
    dot.setAttribute("aria-label", `Ver anuncio ${index + 1}`);
    dot.addEventListener("click", () => {
      setActiveSlide(index);
      startAutoplay();
    });
    dotsContainer?.append(dot);
    dots.push(dot);
  });

  prevButton?.addEventListener("click", () => {
    setActiveSlide(currentIndex - 1);
    startAutoplay();
  });

  nextButton?.addEventListener("click", () => {
    setActiveSlide(currentIndex + 1);
    startAutoplay();
  });

  slider.addEventListener("mouseenter", stopAutoplay);
  slider.addEventListener("mouseleave", startAutoplay);
  slider.addEventListener("focusin", stopAutoplay);
  slider.addEventListener("focusout", startAutoplay);

  setActiveSlide(currentIndex);
  startAutoplay();
});

const profileModal = document.querySelector('[data-js="profile-modal"]');
const profileDialog = profileModal?.querySelector(".profile-modal__dialog");
const profileTitle = profileModal?.querySelector('[data-js="profile-title"]');
const profileBody = profileModal?.querySelector('[data-js="profile-body"]');
const profileOpenButtons = document.querySelectorAll('[data-js="profile-open"]');
const profileCloseButtons = document.querySelectorAll('[data-js="profile-close"]');
let lastProfileTrigger = null;

const closeProfileModal = () => {
  if (!profileModal) {
    return;
  }

  profileModal.hidden = true;
  document.body.classList.remove("modal-is-open");

  if (lastProfileTrigger) {
    lastProfileTrigger.focus();
  }
};

profileOpenButtons.forEach((button) => {
  button.addEventListener("click", () => {
    if (!profileModal || !profileBody || !profileTitle) {
      return;
    }

    const templateId = button.dataset.profileTarget;
    const template = templateId ? document.getElementById(templateId) : null;
    const card = button.closest(".student-teacher-card, .student-name-card");
    const title = card?.querySelector("h2")?.textContent?.trim() || "Perfil";

    if (!(template instanceof HTMLTemplateElement)) {
      return;
    }

    lastProfileTrigger = button;
    profileTitle.textContent = title;
    profileBody.replaceChildren(template.content.cloneNode(true));
    profileModal.hidden = false;
    document.body.classList.add("modal-is-open");
    profileDialog?.focus();
  });
});

profileCloseButtons.forEach((button) => {
  button.addEventListener("click", closeProfileModal);
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && profileModal && !profileModal.hidden) {
    closeProfileModal();
  }
});

const imageZoomModal = document.querySelector('[data-js="image-zoom-modal"]');
const imageZoomDialog = imageZoomModal?.querySelector(".image-zoom-modal__dialog");
const imageZoomImage = imageZoomModal?.querySelector('[data-js="image-zoom-image"]');
const imageZoomOpenButtons = document.querySelectorAll('[data-js="image-zoom-open"]');
const imageZoomCloseButtons = document.querySelectorAll('[data-js="image-zoom-close"]');
let lastImageZoomTrigger = null;

const closeImageZoomModal = () => {
  if (!imageZoomModal) {
    return;
  }

  imageZoomModal.hidden = true;
  document.body.classList.remove("modal-is-open");

  if (lastImageZoomTrigger) {
    lastImageZoomTrigger.focus();
  }
};

imageZoomOpenButtons.forEach((button) => {
  button.addEventListener("click", () => {
    if (!imageZoomModal || !imageZoomImage) {
      return;
    }

    const imageSrc = button.dataset.imageSrc;
    const imageAlt = button.dataset.imageAlt || "Imagen ampliada";

    if (imageSrc) {
      imageZoomImage.src = imageSrc;
    }

    imageZoomImage.alt = imageAlt;
    lastImageZoomTrigger = button;
    imageZoomModal.hidden = false;
    document.body.classList.add("modal-is-open");
    imageZoomDialog?.focus();
  });
});

imageZoomCloseButtons.forEach((button) => {
  button.addEventListener("click", closeImageZoomModal);
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && imageZoomModal && !imageZoomModal.hidden) {
    closeImageZoomModal();
  }
});

