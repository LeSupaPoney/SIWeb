<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

global $entityManager;

function optionsCatalogue(Request $request, Response $response, $args)
{

        // Evite que le front demande une confirmation à chaque modification
        $response = $response->withHeader("Access-Control-Max-Age", 90);

        return addHeaders($response);
}

function hello(Request $request, Response $response, $args)
{
        $array = [];
        $array["nom"] = $args['name'];
        $response->getBody()->write(json_encode($array));
        return $response;
}


function getSearchCatalogue(Request $request, Response $response, $args) {
    global $entityManager;

    $queryParams = $request->getQueryParams();
    $nameFilter = $queryParams['name'] ?? null;
    $priceFilter = $queryParams['price'] ?? null;
    $categoryFilter = $queryParams['category'] ?? null;

    $criteria = [];
    if ($nameFilter !== null) {
        $criteria['name'] = $nameFilter;
    }
    if ($priceFilter !== null) {
        $criteria['price'] = $priceFilter;
    }
    if ($categoryFilter !== null) {
        $criteria['category'] = $categoryFilter;
    }

    $productRepository = $entityManager->getRepository('Produit');
    $products = $productRepository->findBy($criteria);

    $data = array_map(function ($product) {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'description' => $product->getDescription(),
            'picture' => $product->getPicture(),
            'category' => $product->getCategory()
        ];
    }, $products);

    $response->getBody()->write(json_encode($data));

    return addHeaders($response)->withHeader('Content-Type', 'application/json');
}

//api nécessite un jwt valide
function getCatalogue (Request $request, Response $response, $args) {
        global $entityManager;

    if ($entityManager === null) {
        $response = $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode(['error' => 'Internal server error']));
        return $response;
    }
    $productRepository = $entityManager->getRepository('Produit');
    $products = $productRepository->findAll();

    $data = array_map(function ($product) {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'description' => $product->getDescription(),
            'picture' => $product->getPicture(),
            'category' => $product->getCategory()
        ];
    }, $products);

    $response->getBody()->write(json_encode($data));

    return addHeaders($response)->withHeader('Content-Type', 'application/json');
}

function optionsUtilisateur(Request $request, Response $response, $args)
{

        // Evite que le front demande une confirmation à chaque modification
        $response = $response->withHeader("Access-Control-Max-Age", 600);

        return addHeaders($response);
}

// API Nécessitant un Jwt valide
function getUtilisateur(Request $request, Response $response, $args) {
    global $entityManager;

    $payload = getJWTToken($request);
    $login  = $payload->userid;

    $utilisateurRepository = $entityManager->getRepository('Utilisateurs');
    $utilisateur = $utilisateurRepository->findOneBy(array('login' => $login));
    if ($utilisateur) {
        $data = array('nom' => $utilisateur->getNom(), 'prenom' => $utilisateur->getPrenom());
        $response = addHeaders($response);
        $response = createJwT($response);
        $response->getBody()->write(json_encode($data));
    } else {
        $response = $response->withStatus(404);
    }

    return addHeaders($response);
}

// APi d'authentification générant un JWT
function postLogin(Request $request, Response $response, $args)
{
    global $entityManager;
    $body = $request->getParsedBody();
    $login = $body['login'] ?? "";
    $pass = $body['password'] ?? "";

    if (!preg_match("/[a-zA-Z0-9]{1,20}/", $login) || !preg_match("/[a-zA-Z0-9]{1,20}/", $pass)) {
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['error' => 'Invalid login or password format']));
    }

    $utilisateurRepository = $entityManager->getRepository('Utilisateurs');
    $utilisateur = $utilisateurRepository->findOneBy(array('login' => $login));

    if ($utilisateur && password_verify($pass, $utilisateur->getPassword())) {
        $response = addHeaders($response);
        $response = createJwT($response);
        $data = array('nom' => $utilisateur->getNom(), 'prenom' => $utilisateur->getPrenom());
        $response->getBody()->write(json_encode($data));
    } else {
        $response = $response->withStatus(403)->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['error' => 'Login failed']));
    }

    return addHeaders($response);
}

function createUser(Request $request, Response $response, $args) {
    global $entityManager;
    $body = $request->getParsedBody();
    $errors = [];

    // Liste des champs requis
    $requiredFields = [
        'nom' => 'Nom is required',
        'prenom' => 'Prénom is required',
        'adresse' => 'Adresse is required',
        'codepostal' => 'Code Postal is required',
        'ville' => 'Ville is required',
        'email' => 'Email is required',
        'sexe' => 'Sexe is required',
        'login' => 'Login is required',
        'password' => 'Password is required',
        'telephone' => 'Téléphone is required',
    ];

    foreach ($requiredFields as $field => $errorMessage) {
        if (empty($body[$field])) {
            $errors[$field] = $errorMessage;
        }
    }

    // Si des erreurs sont présentes
    if (!empty($errors)) {
        $response->getBody()->write(json_encode(['errors' => $errors]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    try {
        $errors[$field] = "TRY TO CREATE USER";
        $user = new Utilisateurs();
        $user->setNom($body['nom']);
        $user->setPrenom($body['prenom']);
        $user->setAdresse($body['adresse']);
        $user->setCodePostal($body['codepostal']);
        $user->setVille($body['ville']);
        $user->setEmail($body['email']);
        $user->setSexe($body['sexe']);
        $user->setLogin($body['login']);
        $user->setTelephone($body['telephone']);
        $user->setPassword(password_hash($body['password'], PASSWORD_DEFAULT));

        $entityManager->persist($user);
        $entityManager->flush();

        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(201)
                        ->write(json_encode(['message' => 'Utilisateur créé avec succès']));
    } catch (\Throwable $th) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(500)
                        ->write(json_encode(['error' => 'Erreur serveur: ' . $th->getMessage()]));
    }
}