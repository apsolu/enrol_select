<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace enrol_select;

use PHPUnit\Framework\Attributes\CoversNothing;
use advanced_testcase;
use cache;

/**
 * Classe pour tester le paramétrage du cache.
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class cache_test extends advanced_testcase {
    /**
     * Initialise un environnement de test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();
    }

    /**
     * Teste le cache des populations.
     *
     * @return void
     */
    public function test_cache_colleges(): void {
        $cache = cache::make('enrol_select', 'colleges');

        $elements = [
            '1' => ['name' => 'Population évaluée', 'roleid' => 1, 'cohorts' => [1, 2, 3], 'min' => 2, 'max' => 3],
            '2' => ['name' => 'Population non évaluée', 'roleid' => 10, 'cohorts' => [4, 8, 16], 'min' => 1, 'max' => 1],
            '4' => ['name' => 'Population FFSU', 'roleid' => 9, 'cohorts' => [9], 'min' => 0, 'max' => 1],
        ];

        // Valide l'enregistrement des données.
        $this->assertSame(count($elements), $cache->set_many($elements));

        // Valide la lecture des données.
        $this->assertSame(count($elements), count($cache->get_many(['1', '2', '4'])));

        // Valide la lecture d'une donnée.
        $result = $cache->get('4');
        $this->assertSame('Population FFSU', $result['name']);
        $this->assertSame(9, $result['roleid']);
        $this->assertSame([9], $result['cohorts']);

        // Valide la suppression d'un élément.
        $this->assertTrue($cache->delete('4'));

        // Valide la non-récupération d'un élément supprimé.
        $this->assertFalse($cache->get('4'));

        // Valide la suppression des 2 éléments restants (en incluant 1 élément non existant, supprimé précédement).
        $this->assertSame(2, $cache->delete_many(['1', '2', '4']));
    }

    /**
     * Teste le cache des utilisateurs.
     *
     * @return void
     */
    public function test_cache_users(): void {
        $cache = cache::make('enrol_select', 'users');

        $elements = [
            '1' => ['cohorts' => [1, 2, 3], 'colleges' => [1, 2], 'enrolments' => [1, 2, 3]],
            '2' => ['cohorts' => [1, 2, 3], 'colleges' => [1, 2], 'enrolments' => [1, 2, 3]],
            '3' => ['cohorts' => [1, 2, 3], 'colleges' => [1, 2], 'enrolments' => [1, 2, 3]],
            '4' => ['cohorts' => [4], 'colleges' => [3], 'enrolments' => [4]],
        ];

        // Valide l'enregistrement des données.
        $this->assertSame(count($elements), $cache->set_many($elements));

        // Valide la lecture des données.
        $this->assertSame(count($elements), count($cache->get_many(['1', '2', '3', '4'])));

        // Valide la lecture d'une donnée.
        $result = $cache->get('3');
        $this->assertSame([1, 2, 3], $result['cohorts']);
        $this->assertSame([1, 2], $result['colleges']);
        $this->assertSame([1, 2, 3], $result['enrolments']);

        $result = $cache->get('4');
        $this->assertSame([4], $result['cohorts']);
        $this->assertSame([3], $result['colleges']);
        $this->assertSame([4], $result['enrolments']);

        // Valide la suppression d'un élément.
        $this->assertTrue($cache->delete('4'));

        // Valide la non-récupération d'un élément supprimé.
        $this->assertFalse($cache->get('4'));

        // Valide la suppression des 3 éléments restants (en incluant 1 élément non existant, supprimé précédement).
        $this->assertSame(3, $cache->delete_many(['1', '2', '3', '4']));
    }
}
