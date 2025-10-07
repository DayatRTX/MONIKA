document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("mainContent");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const body = document.body;

  if (body) {
    body.classList.add("preload-no-transitions");
  }

  // Logika untuk sidebar
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

  const monitoringForm = document.getElementById("monitoringForm");
  if (monitoringForm) {
    function checkActivityTimes() {
      const now = new Date();

      const activityRows = monitoringForm.querySelectorAll("tbody tr");

      activityRows.forEach((row) => {
        const radioButtons = row.querySelectorAll('input[type="radio"]');
        const radioSelesai = row.querySelector(
          'input[type="radio"][value="Selesai"]'
        );

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

        if (radioSelesai) {
          const waktuStandarStr =
            radioSelesai.getAttribute("data-waktu-standar");
          if (!waktuStandarStr) return;

          const [hours, minutes, seconds] = waktuStandarStr.split(":");
          const waktuStandarDate = new Date();
          waktuStandarDate.setHours(hours, minutes, seconds, 0);

          const deadlineDate = new Date(
            waktuStandarDate.getTime() + 60 * 60 * 1000
          );

          const isOverdue = now > deadlineDate;

          if (isOverdue) {
            radioSelesai.disabled = true;

            const checkedRadio = row.querySelector(
              'input[type="radio"]:checked'
            );
            if (!checkedRadio || checkedRadio.value === "Belum") {
              const radioLewat = row.querySelector(
                'input[type="radio"][value="Lewat"]'
              );
              if (radioLewat) {
                radioLewat.checked = true;
              }
            }
          } else {
            radioSelesai.disabled = false;
          }
        }
      });
    }

    checkActivityTimes();
    setInterval(checkActivityTimes, 30000);

    // Logika Autosave
    let formChanged = false;
    monitoringForm.addEventListener("change", function () {
      formChanged = true;
    });

    monitoringForm.addEventListener("submit", function () {
      formChanged = false;
    });

    window.addEventListener("beforeunload", function (event) {
      if (formChanged) {
        const formData = new FormData(monitoringForm);
        const params = new URLSearchParams(formData);
        navigator.sendBeacon("config/autosave.php", params.toString());
      }
    });
  }
});
