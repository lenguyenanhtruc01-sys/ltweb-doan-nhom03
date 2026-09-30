// js/api.js — Hàm tải dữ liệu dùng chung cho cả website
export async function taiJSON(url) {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status} khi tải ${url}`);
    return res.json();
}