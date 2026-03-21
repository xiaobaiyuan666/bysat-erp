import axios from 'axios'

const client = axios.create({
  baseURL: '/api.php',
  timeout: 120000,
})

function unwrapResponse(payload) {
  if (payload?.ok) {
    return payload
  }

  const error = new Error(payload?.message || '请求失败')
  error.payload = payload?.data || null
  throw error
}

export function extractApiError(error) {
  const responsePayload = error?.response?.data

  if (responsePayload && typeof responsePayload === 'object') {
    const wrapped = new Error(responsePayload.message || '请求失败')
    wrapped.payload = responsePayload.data || null
    return wrapped
  }

  if (error instanceof Error) {
    return error
  }

  return new Error('请求失败')
}

export async function fetchBootstrap() {
  const { data } = await client.get('', {
    params: {
      resource: 'bootstrap',
    },
  })

  return unwrapResponse(data)
}

export async function postAction(action, payload = {}) {
  const { data } = await client.post('', {
    action,
    ...payload,
  })

  return unwrapResponse(data)
}

export async function postMultipartAction(action, payload = {}) {
  const formData = new FormData()
  formData.append('action', action)

  Object.entries(payload).forEach(([key, value]) => {
    appendFormValue(formData, key, value)
  })

  const { data } = await client.post('', formData)

  return unwrapResponse(data)
}

function appendFormValue(formData, key, value) {
  if (value === undefined || value === null) {
    return
  }

  if (Array.isArray(value)) {
    value.forEach((item) => appendFormValue(formData, `${key}[]`, item))
    return
  }

  if (value instanceof File || value instanceof Blob) {
    formData.append(key, value)
    return
  }

  formData.append(key, String(value))
}
