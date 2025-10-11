document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("mainContent");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const body = document.body;

  if (body) {
    body.classList.add("preload-no-transitions");
  }

  if (sidebar && mainContent && sidebarToggle) {
    let sidebarIsCollapsed =
      localStorage.getItem("sidebarCollapsed") !== "false";

    const icon = sidebarToggle.querySelector("i");
    if (sidebarIsCollapsed) {
      sidebar.classList.add("collapsed");
      mainContent.classList.add("sidebar-collapsed");
      if (icon) icon.className = "fas fa-bars";
    } else {
      sidebar.classList.remove("collapsed");
      mainContent.classList.remove("sidebar-collapsed");
      if (icon) icon.className = "fas fa-times";
    }

    sidebarToggle.addEventListener("click", function () {
      sidebar.classList.toggle("collapsed");
      mainContent.classList.toggle("sidebar-collapsed");

      const currentIcon = sidebarToggle.querySelector("i");
      if (sidebar.classList.contains("collapsed")) {
        localStorage.setItem("sidebarCollapsed", "true");
        if (currentIcon) currentIcon.className = "fas fa-bars";
      } else {
        localStorage.setItem("sidebarCollapsed", "false");
        if (currentIcon) currentIcon.className = "fas fa-times";
      }
    });
  }

  if (body) {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        body.classList.remove("preload-no-transitions");
      });
    });
  }

  function handleAlatRusakTotal() {
    const rusakRows = document.querySelectorAll("tr.alat-rusak-total");
    rusakRows.forEach((row) => {
      const radioButtons = row.querySelectorAll(".status-radio");
      const komentarInput = row.querySelector(".komentar-input");

      radioButtons.forEach((radio) => {
        if (radio.value !== "Rusak") {
          radio.disabled = true;
        } else {
          if (!radio.checked) {
            radio.checked = true;
            saveData(radio);
          }
          radio.disabled = false;
        }
      });

      if (komentarInput) {
        komentarInput.placeholder = "Alat ini ditandai rusak total.";
      }
    });
  }

  const monitoringForm = document.getElementById("monitoringForm");
  if (monitoringForm) {
    handleAlatRusakTotal();

    function checkActivityTimes() {
      const now = new Date();
      const activityRows = monitoringForm.querySelectorAll("tbody tr");

      activityRows.forEach((row) => {
        if (row.classList.contains("alat-rusak-total")) {
          return;
        }

        const radioButtons = row.querySelectorAll('input[type="radio"]');
        let isCompleted = false;
        radioButtons.forEach((radio) => {
          if (radio.value === "Selesai" && radio.checked) {
            isCompleted = true;
          }
        });

        if (isCompleted) {
          radioButtons.forEach((radio) => {
            radio.disabled = true;
          });
          return;
        }

        const radioSelesai = row.querySelector(
          'input[type="radio"][value="Selesai"]'
        );
        const radioBelum = row.querySelector(
          'input[type="radio"][value="Belum"]'
        );

        if (radioSelesai && radioBelum) {
          radioSelesai.disabled = false;
          radioBelum.disabled = false;

          const waktuStandarStr =
            radioSelesai.getAttribute("data-waktu-standar");
          if (!waktuStandarStr) return;

          const [hours, minutes] = waktuStandarStr.split(":");
          const waktuStandarDate = new Date();
          waktuStandarDate.setHours(hours, minutes, 0, 0);

          const deadlineDate = new Date(
            waktuStandarDate.getTime() + 60 * 60 * 1000
          );
          const isOverdue = now > deadlineDate;

          if (isOverdue) {
            radioSelesai.disabled = true;
            radioBelum.disabled = true;

            const checkedRadio = row.querySelector(
              'input[type="radio"]:checked'
            );
            if (!checkedRadio || checkedRadio.value === "Belum") {
              const radioLewat = row.querySelector(
                'input[type="radio"][value="Lewat"]'
              );
              if (radioLewat) {
                radioLewat.checked = true;
                saveData(radioLewat);
              }
            }
          }
        }
      });
    }

    checkActivityTimes();
    setInterval(checkActivityTimes, 30000);

    function saveData(inputElement) {
      const name = inputElement.name;
      const value = inputElement.value;
      const data = `${encodeURIComponent(name)}=${encodeURIComponent(value)}`;

      fetch("config/autosave.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: data,
      }).catch((error) => console.error("Autosave failed:", error));
    }

    monitoringForm.addEventListener("change", function (event) {
      if (event.target.classList.contains("status-radio")) {
        saveData(event.target);
      }
    });

    let debounceTimer;
    monitoringForm.addEventListener("input", function (event) {
      if (event.target.classList.contains("komentar-input")) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          saveData(event.target);
        }, 1000);
      }
    });
  }
});
