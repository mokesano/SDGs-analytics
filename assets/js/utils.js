// Utility functions

export function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

export function throttle(func, limit) {
  let inThrottle;
  return function(...args) {
    if (!inThrottle) {
      func.apply(this, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
}

export function formatNumber(num) {
  return new Intl.NumberFormat('id-ID').format(num);
}

export function formatDate(dateString) {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(date);
}

export function getSDGColor(sdgCode) {
  const colors = {
    'SDG1': '#E5243B', 'SDG2': '#DDA63A', 'SDG3': '#4C9F38',
    'SDG4': '#C5192D', 'SDG5': '#FF3A21', 'SDG6': '#26BDE2',
    'SDG7': '#FCC30B', 'SDG8': '#A21942', 'SDG9': '#FD6925',
    'SDG10': '#DD1367', 'SDG11': '#FD9D24', 'SDG12': '#BF8B2E',
    'SDG13': '#3F7E44', 'SDG14': '#0A97D9', 'SDG15': '#56C02B',
    'SDG16': '#00689D', 'SDG17': '#19486A'
  };
  return colors[sdgCode] || '#64748B';
}

export function getSDGLabel(sdgCode) {
  const labels = {
    'SDG1': 'No Poverty', 'SDG2': 'Zero Hunger', 'SDG3': 'Good Health',
    'SDG4': 'Quality Education', 'SDG5': 'Gender Equality', 'SDG6': 'Clean Water',
    'SDG7': 'Clean Energy', 'SDG8': 'Decent Work', 'SDG9': 'Industry & Innovation',
    'SDG10': 'Reduced Inequalities', 'SDG11': 'Sustainable Cities', 'SDG12': 'Responsible Consumption',
    'SDG13': 'Climate Action', 'SDG14': 'Life Below Water', 'SDG15': 'Life on Land',
    'SDG16': 'Peace & Justice', 'SDG17': 'Partnerships'
  };
  return labels[sdgCode] || sdgCode;
}
