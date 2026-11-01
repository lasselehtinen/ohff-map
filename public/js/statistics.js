document.addEventListener("DOMContentLoaded", () => {
  const tooltip = document.getElementById("tooltip");
  const value = document.createElement("strong");
  const label = document.createElement("span");

  tooltip.append(value, label);

  function show(element, x, y) {
    const [name, amount] = element.dataset.tip.split("|");

    value.textContent = amount;
    label.textContent = name;
    tooltip.hidden = false;

    const left = Math.min(x + 12, window.innerWidth - tooltip.offsetWidth - 8);
    const top = Math.max(y - tooltip.offsetHeight - 12, 8);

    tooltip.style.left = `${Math.max(left, 8)}px`;
    tooltip.style.top = `${top}px`;
  }

  document.querySelectorAll("[data-tip]").forEach((element) => {
    element.addEventListener("pointermove", (event) => show(element, event.clientX, event.clientY));
    element.addEventListener("pointerleave", () => { tooltip.hidden = true; });
    element.addEventListener("focus", () => {
      const box = element.getBoundingClientRect();
      show(element, box.left + box.width / 2, box.top);
    });
    element.addEventListener("blur", () => { tooltip.hidden = true; });
  });
});
