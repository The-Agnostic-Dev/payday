// Filters the staff table as you type.
(function () {
  const input = document.getElementById('staff-filter');
  if (!input) return;

  const rows = Array.from(document.querySelectorAll('[data-testid="staff"] tbody tr'));
  const count = document.getElementById('staff-count');
  const empty = document.getElementById('staff-empty');

  function words(text) {
    return text.toLowerCase().split(/\s+/).filter(Boolean);
  }

  function apply() {
    const terms = words(input.value);
    let shown = 0;

    rows.forEach(function (row) {
      const haystack = [row.dataset.name, row.dataset.staffNo, row.dataset.department].join(' ');
      const match = terms.every(function (term) {
        return haystack.indexOf(term) !== -1;
      });
      row.hidden = !match;
      if (match) shown++;
    });

    count.textContent = shown + (shown === 1 ? ' staff member' : ' staff');
    empty.hidden = shown !== 0;
  }

  input.addEventListener('input', apply);
  apply();
})();
