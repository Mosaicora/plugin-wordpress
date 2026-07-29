(function () {
  "use strict";

  const strategy = document.getElementById("mosaicora-cache-strategy");
  const revision = document.getElementById("mosaicora-manual-revision");
  if (!strategy || !revision) {
    return;
  }

  function updateRevisionState() {
    const manual = strategy.value === "manual";
    revision.disabled = !manual;
    revision.setAttribute("aria-disabled", manual ? "false" : "true");
  }

  strategy.addEventListener("change", updateRevisionState);
  updateRevisionState();
})();
