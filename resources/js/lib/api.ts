/**
 * Lightweight fetch wrapper with automatic CSRF token injection.
 * Drop-in replacement for axios — no third-party dependency required.
 */

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

interface ApiOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    body?: Record<string, unknown>;
}

export async function api<T = unknown>(url: string, options: ApiOptions = {}): Promise<{ data: T }> {
    const { method = 'GET', body } = options;

    const res = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    const data = await res.json().catch(() => ({})) as T;

    if (!res.ok) {
        const err: any = new Error((data as any)?.message ?? (data as any)?.error ?? `HTTP ${res.status}`);
        err.response = { data, status: res.status };
        throw err;
    }

    return { data };
}

export const apiGet  = <T = unknown>(url: string) => api<T>(url, { method: 'GET' });
export const apiPost = <T = unknown>(url: string, body?: Record<string, unknown>) => api<T>(url, { method: 'POST', body });
