<?php

namespace App\Infrastructure\Command;

use App\Domain\Entity\ProductEntity;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateProductEntries
 *
 * @package App\Infrastructure\Console\Command
 */
class GenerateProductEntries extends Command
{
    const COMMAND_NAME = 'generate-products';

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
             ->setDescription('Generate log entities');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $app['orm.ems']['mysql'];

        $i = 0;
        while ($i < 10) {
            $j = 0;
            while ($j < 10) {
                $log = new ProductEntity();
                $log->setTitle($this->generateRandomString(32));
                $log->setStatus(rand(1, 100));
                $log->setCreated(new \DateTime());
                $log->setUpdated(new \DateTime());

                $entityManager->persist($log);
                $j++;
            }
            $entityManager->flush();
            $i++;
        }

        $output->writeln('Finished');
    }

    private function generateRandomString($length = 255) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
