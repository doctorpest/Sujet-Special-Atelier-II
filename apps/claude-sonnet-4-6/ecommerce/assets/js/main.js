// assets/js/main.js

// ── Quantity spinner ──────────────────────────────────────────
document.querySelectorAll('.qty-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = btn.closest('.qty-row').querySelector('.qty-input');
    let val = parseInt(input.value, 10) || 1;
    const max = parseInt(input.max, 10) || 999;
    if (btn.dataset.dir === '+') val = Math.min(val + 1, max);
    else                          val = Math.max(val - 1, 1);
    input.value = val;
  });
});

// ── Cart: auto-submit on qty change ──────────────────────────
document.querySelectorAll('.cart-qty-input').forEach(input => {
  input.addEventListener('change', () => input.closest('form').submit());
});

// ── Flash auto-dismiss ────────────────────────────────────────
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => el.style.opacity = '0', 5000);
  el.style.transition = 'opacity .5s';
});

// ── Credit card mask ──────────────────────────────────────────
const ccInput = document.getElementById('cc_number');
if (ccInput) {
  ccInput.addEventListener('input', e => {
    let v = e.target.value.replace(/\D/g,'').substring(0,16);
    e.target.value = v.match(/.{1,4}/g)?.join(' ') ?? v;
  });
}

const ccExp = document.getElementById('cc_exp');
if (ccExp) {
  ccExp.addEventListener('input', e => {
    let v = e.target.value.replace(/\D/g,'').substring(0,4);
    if (v.length >= 3) v = v.substring(0,2) + '/' + v.substring(2);
    e.target.value = v;
  });
}
