const API_BASE = 'http://localhost:8080/api';

export async function apiFetch(path: string, options: RequestInit = {}) {
  const token = localStorage.getItem('token');

  const headers = {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  };

  const res = await fetch(`${API_BASE}${path}`, { ...options, headers });

  if (!res.ok) {
    let bodyText = await res.text();
    try {
      const json = JSON.parse(bodyText || '{}');
      const errMsg = json.error || json.message || bodyText || res.statusText;
      throw new Error(`API error ${res.status}: ${JSON.stringify({ error: errMsg })}`);
    } catch (e) {
      throw new Error(`API error ${res.status}: ${bodyText || res.statusText}`);
    }
  }

  return res.json();
}

export const AuthAPI = {
  register: (data: { email: string; password: string }) =>
    apiFetch('/auth/register', { method: 'POST', body: JSON.stringify(data) }),
  login: (data: { email: string; password: string }) =>
    apiFetch('/auth/login', { method: 'POST', body: JSON.stringify(data) }),
};

export const BooksAPI = {
  list: () => apiFetch('/books'),
  create: (book: any) =>
    apiFetch('/books', { method: 'POST', body: JSON.stringify(book) }),
  remove: (id: number) => apiFetch(`/books/${id}`, { method: 'DELETE' }),
};