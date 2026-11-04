<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Pruebas funcionales del flujo de autenticación.
 *
 * Hacen peticiones HTTP reales al kernel, como haría un navegador, y comprueban
 * el código de estado, las redirecciones y el HTML renderizado. Son el
 * equivalente automatizado de los casos P01-P08 de Postman.
 */
final class LoginFlowTest extends WebTestCase
{
    private const string USERNAME = 'phpunit';
    private const string PASSWORD = 'PhpUnit123!';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->createTestUserIfMissing();
    }

    public function testPublicPagesAreAccessible(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'Bienvenido a mi primera aplicación Symfony',
            (string) $this->client->getResponse()->getContent(),
        );

        $this->client->request('GET', '/acerca-de');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'Acerca de este proyecto',
            (string) $this->client->getResponse()->getContent(),
        );
    }

    public function testLoginPageShowsFormAndCsrfToken(): void
    {
        $crawler = $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('input[name="_username"]'));
        self::assertCount(1, $crawler->filter('input[name="_password"]'));
        self::assertCount(1, $crawler->filter('input[name="_csrf_token"]'));
        self::assertSame(
            'Entrar',
            trim($crawler->filter('button[type="submit"]')->text()),
        );
    }

    public function testDashboardRequiresAuthentication(): void
    {
        $this->client->request('GET', '/dashboard');

        self::assertResponseStatusCodeSame(302);
        self::assertSame('/login', $this->redirectPath());
    }

    public function testLoginWithValidCredentialsRedirectsToDashboard(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Entrar', [
            '_username' => self::USERNAME,
            '_password' => self::PASSWORD,
        ]);

        self::assertResponseStatusCodeSame(302);
        self::assertSame('/dashboard', $this->redirectPath());

        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'Bienvenido, '.self::USERNAME,
            (string) $this->client->getResponse()->getContent(),
        );
    }

    public function testLoginWithWrongPasswordShowsError(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Entrar', [
            '_username' => self::USERNAME,
            '_password' => 'contraseña-incorrecta',
        ]);

        self::assertResponseStatusCodeSame(302);
        self::assertSame('/login', $this->redirectPath());

        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'Credenciales no válidas',
            (string) $this->client->getResponse()->getContent(),
        );
    }

    public function testLogoutRemovesAccessToDashboard(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Entrar', [
            '_username' => self::USERNAME,
            '_password' => self::PASSWORD,
        ]);
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/logout');

        self::assertResponseStatusCodeSame(302);
        self::assertSame('/', $this->redirectPath());

        $this->client->request('GET', '/dashboard');

        self::assertResponseStatusCodeSame(302);
        self::assertSame('/login', $this->redirectPath());
    }

    private function redirectPath(): string
    {
        $location = (string) $this->client->getResponse()->headers->get('Location');

        return (string) parse_url($location, \PHP_URL_PATH);
    }

    private function createTestUserIfMissing(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        if (null !== $entityManager->getRepository(User::class)->findOneBy(['username' => self::USERNAME])) {
            return;
        }

        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setUsername(self::USERNAME)
            ->setEmail(self::USERNAME.'@example.test');

        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));

        $entityManager->persist($user);
        $entityManager->flush();
    }
}
