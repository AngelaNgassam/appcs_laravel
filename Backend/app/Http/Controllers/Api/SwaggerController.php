<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="API Cartes Scolaires",
 *     version="1.0.0",
 *     description="API complète pour la gestion des cartes scolaires",
 *     @OA\Contact(
 *         email="support@cartes-scolaires.com",
 *         name="Support API"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Serveur de développement"
 * )
 *
 * @OA\Server(
 *     url="https://api.cartes-scolaires.com/api/v1",
 *     description="Serveur de production"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Utiliser le token JWT reçu lors de la connexion"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints d'authentification"
 * )
 *
 * @OA\Tag(
 *     name="Users",
 *     description="Gestion des utilisateurs"
 * )
 *
 * @OA\Tag(
 *     name="Etablissements",
 *     description="Gestion des établissements scolaires"
 * )
 *
 * @OA\Tag(
 *     name="Années Académiques",
 *     description="Gestion des années académiques"
 * )
 *
 * @OA\Tag(
 *     name="Classes",
 *     description="Gestion des classes"
 * )
 *
 * @OA\Tag(
 *     name="Eleves",
 *     description="Gestion des élèves"
 * )
 *
 * @OA\Tag(
 *     name="Photos",
 *     description="Gestion des photos d'élèves"
 * )
 *
 * @OA\Tag(
 *     name="Cartes",
 *     description="Gestion des cartes scolaires"
 * )
 *
 * @OA\Tag(
 *     name="Modèles de Cartes",
 *     description="Gestion des modèles de cartes"
 * )
 *
 * @OA\Tag(
 *     name="Historique",
 *     description="Historique des actions"
 * )
 *
 * @OA\Tag(
 *     name="Notifications",
 *     description="Gestion des notifications"
 * )
 */
class SwaggerController extends Controller
{
    //
}
