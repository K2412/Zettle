/** The XSRF token Laravel sets as a cookie, for the `X-XSRF-TOKEN` header on plain fetches. */
export function xsrfToken(): string {
    const match = document.cookie.match(/(^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[2]) : '';
}
