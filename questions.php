<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use function Laravel\Prompts\{confirm, info, multiselect, select, text};

class QuestionTree
{
    private array $answers = [
        'project_name' => 'laravel',
        'server_contact' => '',
        'needs_traefik' => true,
        'network' => '',
        'php_version' => '8.4',
        'php_extensions' => [],
        'database' => 'sqlite',
        'starter_kit' => 'none',
        'starter_kit_stack' => '',
        'starter_kit_options' => [],
        'mono_repo' => 'none',
        'testing_framework' => 'pest',
        'queue' => 'none',
        'queue_driver' => '',
        'features' => [],
        'javascript_package_manager' => '',
        'initialize_git' => true
    ];

    private array $config = [];

    private array $defaultExtensions = [
        'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 
        'mysqli', 'opcache', 'openssl', 'pcntl', 'pcre', 'pdo_mysql', 
        'pdo_pgsql', 'redis', 'session', 'tokenizer', 'xml', 'zip'
    ];

    public function __construct()
    {
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        $configFile = '/app/config.json';
        if (!file_exists($configFile)) {
            throw new RuntimeException('Configuration file not found');
        }

        $this->config = json_decode(file_get_contents($configFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid configuration file');
        }
    }

    public function run(): array
    {
        $this->promptForBasicQuestions();
        $this->promptForDatabase();
        $this->promptForStarterKit();
        $this->promptForQueue();
        $this->promptForFeatures();
        $this->promptForJavascriptPackageManager();
        $this->promptForInitializeGit();

        return $this->answers;
    }

    private function promptForBasicQuestions(): void
    {
        $this->answers['project_name'] = text(
            label: 'What is the name of your project?',
            default: $this->answers['project_name'],
            required: true
        );

        $this->answers['server_contact'] = text(
            label: '🤖 Server Contact',
            placeholder: 'E.g. admin@example.com',
            required: true,
            validate: fn (string $value) => match (true) {
                !filter_var($value, FILTER_VALIDATE_EMAIL) => 'Please enter a valid email address.',
                strlen($value) > 255 => 'The email must not exceed 255 characters.',
                default => null
            },
            hint: "Set an email contact who should be notified for Let's Encrypt SSL renewals and other system alerts."
        );

        $this->promptForTraefik();

        $this->answers['php_version'] = select(
            label: '👉 What PHP version would you like to use?',
            options: [
              '8.4' => 'PHP 8.4 (Recommended)',
              '8.3' => 'PHP 8.3',
              '8.2' => 'PHP 8.2'
            ]
        );

        $this->promptForPhpExtensions();
    }

    private function promptForTraefik(): void
    {
      $hasTraefik = confirm(
          label: "Do you already have a reverse proxy in development?",
          default: false,
          hint: "If you don't know, select no."
      );

      $this->answers['needs_traefik'] = !$hasTraefik;

      if (!$this->answers['needs_traefik']) {
          $this->answers['network'] = select(
            label: 'Select the network to which the services should be attached.',
            options: $this->config['docker_networks']
        );
      }
    }

    private function promptForPhpExtensions(): void
    {
        while (true) {
            info(
                "Default extensions:\n" .
                implode(', ', $this->defaultExtensions) . "\n\n" .
                "See available extensions:\n" .
                "https://serversideup.net/docker-php/available-extensions\n\n" .
                "Enter additional extensions as a comma-separated list (no spaces).\n" .
                "Example: gd,imagick,intl"
            );
    
            $input = text(
                label: 'Enter comma separated extensions below or press ENTER to use default extensions.'
            );
    
            if (empty($input)) {
                $this->answers['php_extensions'] = [];
                break;
            }
    
            $extensions = array_filter(
                array_map('trim', explode(',', $input)),
                fn($ext) => !empty($ext)
            );
    
            if (!empty($extensions)) {
                info(
                    "These extensions names must be supported in the PHP version you selected.\n" .
                    "Learn more here: https://serversideup.net/docker-php/available-extensions\n\n" .
                    "PHP Version: {$this->answers['php_version']}\n" .
                    "Extensions:\n" . 
                    implode("\n", array_map(fn($ext) => "- $ext", $extensions))
                );
    
                if (confirm('Do you confirm these extensions?')) {
                    $this->answers['php_extensions'] = $extensions;
                    break;
                }
    
                // Si non confirmé, on continue la boucle
                info('Returning to extension selection...');
                continue;
            }
    
            // Si on arrive ici, c'est qu'il n'y avait pas d'extensions valides
            $this->answers['php_extensions'] = [];
            break;
        }
    }

    private function promptForDatabase(): void
    {
      $this->answers['database'] = select(
        label: "Which database will your application use?",
        options: [
            'sqlite' => 'SQLite',
            'mysql' => 'MySQL',
            'mariadb' => 'MariaDB',
            'postgres' => 'PostgreSQL',
          ]
      );
    }

    private function promptForStarterKit(): void
    {
        $this->answers['starter_kit'] = select(
          label: 'Would you like to install a starter kit?',
          options: [
              'none' => 'No starter kit',
              'breeze' => 'Laravel Breeze',
              'jetstream' => 'Laravel Jetstream',
          ]
      );

        if ($this->answers['starter_kit'] === 'breeze') {
            $this->promptForBreezeOptions();
        } else if ($this->answers['starter_kit'] === 'jetstream') {
            $this->promptForJetstream();
        }

        $this->promptForTestingFramework();
    }

    private function promptForBreezeOptions(): void
    {
        $this->answers['starter_kit_stack'] = select(
          label: 'Which Breeze stack would you like to install?',
          options: [
              'blade' => 'Blade with Alpine',
              'livewire' => 'Livewire (Volt Class API) with Alpine',
              'livewire-functional' => 'Livewire (Volt Functional API) with Alpine',
              'react' => 'React with Inertia',
              'vue' => 'Vue with Inertia',
              'api' => 'API only',
          ],
      );

      if ($this->answers['starter_kit_stack'] === 'react' || $this->answers['starter_kit_stack'] === 'vue') {
        $this->answers['starter_kit_options'] = multiselect(
            label: 'Would you like any optional features?',
            options: [
                'dark' => 'Dark mode',
                'ssr' => 'Inertia SSR',
                'typescript' => 'TypeScript',
                'eslint' => 'ESLint with Prettier',
            ]
        );
      } else if ($this->answers['starter_kit_stack'] !== 'api') {
        $this->answers['starter_kit_options'] = confirm(
            label: 'Would you like dark mode support?',
            default: false
        ) ? ['dark'] : [];        
      } else {
        info("You have chosen an api stack without a frontend.");

        $this->answers['mono_repo'] = select(
            label: "Do you want to initialize a monorepo with Nuxt or Next?",
            options: [
                'none' => "No, I'm not a fan of monorepo.",
                'nuxt' => "Yes, Nuxt, because you're the Vue to my heart!",
                'next' => 'Yes, Next, because React-ions speak louder than words!'
            ]
        );
      }
    }

    private function promptForJetStream(): void
    {
      $this->answers['starter_kit_stack'] = select(
          label: 'Which Jetstream stack would you like to install?',
          options: [
              'livewire' => 'Livewire',
              'inertia' => 'Vue with Inertia',
          ]
      );

      $this->answers['starter_kit_options'] = multiselect(
          label: 'Would you like any optional features?',
          options: [
              'api' => 'API support',
              'dark' => 'Dark mode',
              'verification' => 'Email verification',
              'teams' => 'Team support',
          ]
      );
    }

    private function promptForTestingFramework(): void
    {
        $this->answers['testing_framework'] = select(
          label: 'Which testing framework do you prefer?',
          options: ['Pest', 'PHPUnit'],
          default: 'Pest'
      );
    }

    private function promptForQueue(): void
    {
      $this->answers['queue'] = select(
          label: "Which Queue Service will your application use?",
          options: [
              'none' => 'None',
              'horizon' => 'Horizon (Recommended)',
              'queue' => 'Queues native',
          ]
      );

      if ($this->answers['queue'] === 'queue') {
        $this->answers['queue_driver'] = select(
            label: "What service to use for queues?",
            options: [
                'valkey' => 'Valkey (Recommended)',
                'redis' => 'Redis',
                'database' => 'Database'
            ]
        );
      } else if ($this->answers['queue'] === 'horizon') {
        $this->answers['queue_driver'] = select(
            label: "What service to use for Horizon?",
            options: [
                'valkey' => 'Valkey (Recommended)',
                'redis' => 'Redis'
            ]
        );
      }
    }

    private function promptForFeatures(): void
    {
      $this->answers['features'] = multiselect(
          label: 'Would you like any optional features?',
          options: [
              'schedule' => 'Task Scheduling',
              'reverb' => 'Reverb'
          ]
      );
    }

    private function promptForJavascriptPackageManager(): void
    {
      if ($this->isApiOnlyWithoutMonoRepo()) {
          return;
      }
  
      $this->answers['javascript_package_manager'] = select(
          label: 'Choose your JavaScript package manager',
          options: [
              'npm',
              'yarn',
              'pnpm',
              'bun'
          ]
      );
    }
    
    private function isApiOnlyWithoutMonoRepo(): bool
    {
      return $this->answers['starter_kit'] === 'breeze' 
          && $this->answers['starter_kit_stack'] === 'api' 
          && $this->answers['mono_repo'] === 'none';
    }

    private function promptForInitializeGit(): void
    {
      $this->answers['initialize_git'] = confirm(label: 'Would you like to initialize a Git repository?');
    }
}

$questionTree = new QuestionTree();
$answers = $questionTree->run();

file_put_contents('/app/output/result.json', json_encode($answers, JSON_PRETTY_PRINT));
