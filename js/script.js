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
      localStorage.getItem("sidebarCollapsed") === "true";

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
    // Fungsi untuk memeriksa dan menonaktifkan status 'Selesai'
    function checkActivityTimes() {
      const now = new Date();

      const radioSelesai = document.querySelectorAll(
        'input[type="radio"][value="Selesai"]'
      );

      radioSelesai.forEach((radio) => {
        const waktuStandarStr = radio.getAttribute("data-waktu-standar");

        const [hours, minutes, seconds] = waktuStandarStr.split(":");
        const waktuStandarDate = new Date();
        waktuStandarDate.setHours(hours, minutes, seconds, 0);

        const deadlineDate = new Date(
          waktuStandarDate.getTime() + 60 * 60 * 1000
        );

        const isOverdue = now > deadlineDate;

        if (isOverdue) {
          radio.disabled = true;

          if (radio.checked) {
            const activityId = radio.name.match(/\[(\d+)\]/)[1];
            const radioLewat = document.querySelector(
              `input[name="status[${activityId}]"][value="Lewat"]`
            );
            if (radioLewat) {
              radioLewat.checked = true;
            }
          }
        } else {
          radio.disabled = false;
        }
      });
    }

    checkActivityTimes();
    setInterval(checkActivityTimes, 30000); // Cek setiap 30 detik

    // Logika Autosave
    let formChanged = false;
    monitoringForm.addEventListener("change", function () {
      formChanged = true;
    });

    window.addEventListener("beforeunload", function (event) {
      if (formChanged) {
        const formData = new FormData(monitoringForm);
        const params = new URLSearchParams(formData);
        // Pastikan path ini benar jika autosave.php dipindah
        navigator.sendBeacon("config/autosave.php", params.toString());
      }
    });
  }
});
