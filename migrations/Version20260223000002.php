<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insertion des données de démonstration (admin, user, événements)';
    }

    public function up(Schema $schema): void
    {
        // Password: password123  (hashed with bcrypt)
        $hash = '$2y$13$H.8OfpJiMDq/Hn3F3RLlguGfMkBKfpVMpuFovfK5Z8jvTaIe1c/Ey';

        $this->addSql("INSERT INTO `user` (username, roles, password_hash) VALUES
            ('admin', '[\"ROLE_ADMIN\"]', '$hash'),
            ('user1', '[\"ROLE_USER\"]', '$hash')
        ");

        $this->addSql("INSERT INTO event (title, description, date, location, seats, created_at) VALUES
            ('Conférence Tech Sousse 2026',
             'Une journée complète dédiée aux dernières innovations technologiques : Intelligence Artificielle, Cloud Computing, Cybersécurité et DevOps. Venez rencontrer des experts et networker avec la communauté tech tunisienne.',
             '2026-04-15 09:00:00', 'Palais des Congrès, Sousse', 200, NOW()),

            ('Hackathon National ISSAT',
             'Participez au plus grand hackathon universitaire de Tunisie ! 48h pour concevoir et développer des solutions innovantes autour du thème de la transformation numérique. Prix pour les 3 meilleures équipes.',
             '2026-04-25 08:00:00', 'ISSAT Sousse — Amphithéâtre A', 100, NOW()),

            ('Workshop Symfony & Docker',
             'Formation pratique et intensive sur Symfony 6 et la conteneurisation avec Docker. Au programme : architecture MVC, API REST, JWT, déploiement avec Docker Compose. Niveau intermédiaire requis.',
             '2026-05-05 14:00:00', 'ISSAT Sousse — Salle Informatique 3', 30, NOW()),

            ('Startup Weekend Sousse',
             'Le Startup Weekend est un événement de 54 heures où des développeurs, designers et entrepreneurs se réunissent pour créer des startups. Pitchez votre idée, formez une équipe et lancez votre projet !',
             '2026-05-16 18:00:00', 'Hôtel Sousse Palace', 150, NOW()),

            ('Journée Open Source',
             'Découvrez l\'écosystème open source : Linux, Kubernetes, PostgreSQL, et plus encore. Ateliers, démonstrations live et contributions à des projets open source.',
             '2026-06-01 09:30:00', 'Campus ISSAT Sousse', 80, NOW())
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM `user` WHERE username IN ('admin', 'user1')");
        $this->addSql("DELETE FROM event WHERE title IN (
            'Conférence Tech Sousse 2026',
            'Hackathon National ISSAT',
            'Workshop Symfony & Docker',
            'Startup Weekend Sousse',
            'Journée Open Source'
        )");
    }
}
