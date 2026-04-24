// Tests de performance k6 - APPCS Laravel
// Outil : k6 (Grafana Labs) - https://k6.io
//
// Ce script simule des utilisateurs réels accédant à l'application
// et vérifie que les temps de réponse et taux d'erreur restent
// dans les limites acceptables.
//
// Lancer localement :
//   k6 run k6/performance-test.js
//
// Avec une URL personnalisée :
//   k6 run -e BASE_URL=http://monserveur.com k6/performance-test.js


import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Métriques personnalisées
const errorRate      = new Rate('error_rate');       // % d'erreurs
const responseTime   = new Trend('response_time_ms'); // temps de réponse
const requestsTotal  = new Counter('requests_total'); // total requêtes

// ── Scénario de charge ───────────────────────────────────────
// Simule une journée type : montée progressive, pic, descente
export const options = {
  stages: [
    { duration: '30s', target: 5  }, // Montée : 0 → 5 utilisateurs
    { duration: '1m',  target: 10 }, // Charge nominale : 10 utilisateurs
    { duration: '30s', target: 20 }, // Pic de charge : 20 utilisateurs
    { duration: '30s', target: 10 }, // Redescente : 20 → 10
    { duration: '30s', target: 0  }, // Arrêt progressif : 10 → 0
  ],

  //  Seuils de qualité — le test échoue si ces seuils sont dépassés
  thresholds: {
    // 95% des requêtes doivent répondre en moins de 2 secondes
    http_req_duration: ['p(95)<2000'],
    // Moins de 5% d'erreurs HTTP autorisées
    http_req_failed: ['rate<0.05'],
    // Métrique personnalisée : taux d'erreur < 5%
    error_rate: ['rate<0.05'],
  },
};

// URL de base - peut être surchargée par variable d'environnement
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

// Scénario principal
export default function () {

  // Test 1 : Page d'accueil
  const homeRes = http.get(`${BASE_URL}/`, {
    tags: { name: 'HomePage' },
    timeout: '10s',
  });

  check(homeRes, {
    'Accueil - statut 200 ou 302': (r) => [200, 302].includes(r.status),
    'Accueil - temps < 2s':        (r) => r.timings.duration < 2000,
    'Accueil - pas d erreur 500':  (r) => r.status !== 500,
  });

  errorRate.add(homeRes.status >= 400);
  responseTime.add(homeRes.timings.duration);
  requestsTotal.add(1);

  sleep(0.5); // Pause entre les requêtes (comportement humain)

  // Test 2 : Endpoint API santé
  const apiRes = http.get(`${BASE_URL}/api/health`, {
    headers: { 'Accept': 'application/json' },
    tags: { name: 'API_Health' },
  });

  check(apiRes, {
    'API - statut acceptable':  (r) => [200, 404, 401].includes(r.status),
    'API - temps < 1s':         (r) => r.timings.duration < 1000,
  });

  errorRate.add(apiRes.status >= 500);
  requestsTotal.add(1);

  sleep(1); // Pause réaliste entre les pages
}

// Rapport de fin de test
export function handleSummary(data) {
  const p95        = data.metrics.http_req_duration?.values?.['p(95)'] || 0;
  const errRate    = (data.metrics.http_req_failed?.values?.rate || 0) * 100;
  const totalReqs  = data.metrics.http_reqs?.values?.count || 0;
  const medianTime = data.metrics.http_req_duration?.values?.med || 0;
  const throughput = data.metrics.http_reqs?.values?.rate || 0;

  console.log('');
  console.log('   RAPPORT DE PERFORMANCE - APPCS LARAVEL  ');
  console.log(`Requêtes totales   : ${totalReqs}`);
  console.log(`Temps médian       : ${medianTime.toFixed(0)} ms`);
  console.log(`Temps P95          : ${p95.toFixed(0)} ms`);
  console.log(`Taux d'erreur      : ${errRate.toFixed(2)} %`);
  console.log(`Débit              : ${throughput.toFixed(1)} req/s`);
  console.log('───────────────────────────────────────────');
  console.log(`Seuil P95 < 2000ms : ${p95 < 2000 ? ' OK' : ' DÉPASSÉ'}`);
  console.log(`Erreurs < 5%       : ${errRate < 5 ? ' OK' : ' DÉPASSÉ'}`);
  console.log('═══════════════════════════════════════════');

  return {
    'k6-results-summary.json': JSON.stringify(data, null, 2),
  };
}
