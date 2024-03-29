<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2021 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Fisharebest\Webtrees\Http\RequestHandlers;

use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Submission;
use Fisharebest\Webtrees\Tree;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_search;
use function assert;
use function is_string;
use function redirect;

use const PHP_INT_MAX;

/**
 * Show a submission's page.
 */
class SubmissionPage implements RequestHandlerInterface
{
    use ViewResponseTrait;

    // Show the submission's facts in this order:
    private const FACT_ORDER = [
        1 => 'SUBN:SUBM',
        'SUBN:FAMF',
        'SUBN:TEMP',
        'SUBN:ANCE',
        'SUBN:DESC',
        'SUBN:ORDI',
        'SUBN:OBJE',
        'SUBN:RIN',
        'SUBN:NOTE',
        'SUBN:CHAN',
    ];

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $xref = $request->getAttribute('xref');
        assert(is_string($xref));

        $record = Registry::submissionFactory()->make($xref, $tree);
        $record = Auth::checkSubmissionAccess($record, false);

        // Redirect to correct xref/slug
        $slug = Registry::slugFactory()->make($record);

        if ($record->xref() !== $xref || $request->getAttribute('slug') !== $slug) {
            return redirect($record->url(), StatusCodeInterface::STATUS_MOVED_PERMANENTLY);
        }

        return $this->viewResponse('gedcom-record-page', [
            'facts'            => $this->facts($record),
            'record'           => $record,
            'families'         => new Collection(),
            'individuals'      => new Collection(),
            'media_objects'    => new Collection(),
            'meta_description' => '',
            'meta_robots'      => 'index,follow',
            'notes'            => new Collection(),
            'sources'          => new Collection(),
            'title'            => $record->fullName(),
            'tree'             => $tree,
        ]);
    }

    /**
     * @param Submission $record
     *
     * @return Collection<Fact>
     */
    private function facts(Submission $record): Collection
    {
        return $record->facts()
            ->sort(static function (Fact $x, Fact $y): int {
                $sort_x = array_search($x->tag(), self::FACT_ORDER, true) ?: PHP_INT_MAX;
                $sort_y = array_search($y->tag(), self::FACT_ORDER, true) ?: PHP_INT_MAX;

                return $sort_x <=> $sort_y;
            });
    }
}
