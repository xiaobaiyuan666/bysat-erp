const currencyCache = new Map()

function getCurrencyFormatter(currency = 'CNY') {
  if (!currencyCache.has(currency)) {
    currencyCache.set(
      currency,
      new Intl.NumberFormat('zh-CN', {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
      }),
    )
  }

  return currencyCache.get(currency)
}

export function formatCurrency(amount, currency = 'CNY') {
  return getCurrencyFormatter(currency).format(Number(amount || 0))
}

export function formatPercent(value) {
  return `${Number(value || 0).toFixed(1)}%`
}

export function formatDate(dateValue) {
  if (!dateValue) {
    return '--'
  }

  return String(dateValue).slice(0, 10)
}

export function formatDateTime(value) {
  if (!value) {
    return '--'
  }

  const date = new Date(value.replace(' ', 'T'))

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

export function formatFileSize(size) {
  const value = Number(size || 0)

  if (value >= 1024 * 1024) {
    return `${(value / (1024 * 1024)).toFixed(1)} MB`
  }

  if (value >= 1024) {
    return `${(value / 1024).toFixed(1)} KB`
  }

  return `${value} B`
}

export function toneToTagType(tone) {
  const mapping = {
    danger: 'danger',
    warning: 'warning',
    success: 'success',
    info: 'primary',
    neutral: 'info',
  }

  return mapping[tone] || 'info'
}
