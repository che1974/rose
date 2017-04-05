<?php
/**
 * @copyright 2017 Roman Parpalak
 * @license   MIT
 */

namespace S2\Rose\Entity;

use S2\Rose\Storage\FulltextIndexContent;

/**
 * Class FulltextResult
 */
class FulltextResult
{
	/**
	 * @var int
	 */
	protected $tocSize = 0;

	/**
	 * @var FulltextQuery
	 */
	protected $query;

	/**
	 * @var FulltextIndexContent
	 */
	protected $fulltextIndexContent;

	/**
	 * FulltextResult constructor.
	 *
	 * @param FulltextQuery        $query
	 * @param FulltextIndexContent $fulltextIndexContent
	 * @param int                  $tocSize
	 */
	public function __construct(FulltextQuery $query, FulltextIndexContent $fulltextIndexContent, $tocSize = 0)
	{
		$this->query                = $query;
		$this->fulltextIndexContent = $fulltextIndexContent;
		$this->tocSize              = $tocSize;
	}

	/**
	 * http://pastexen.com/i/t9Qu6O0TsE.png
	 *
	 * @param int $tocSize
	 * @param int $foundTocEntriesNum
	 *
	 * @return float
	 */
	protected static function frequencyReduction($tocSize, $foundTocEntriesNum)
	{
		if ($tocSize < 5) {
			return 1;
		}

		return exp(-(($foundTocEntriesNum / $tocSize) / 0.38) ^ 2);
	}

	/**
	 * Weight ratio for repeating words in an indexed item.
	 *
	 * @param int $repeatNum
	 *
	 * @return float
	 */
	protected static function repeatWeightRatio($repeatNum)
	{
		return min(0.5 * ($repeatNum - 1) + 1, 4);
	}

	/**
	 * @param float $ratio
	 * @param int   $querySize
	 * @param int   $wordInTocNum
	 *
	 * @return float
	 */
	protected static function fulltextWeight($ratio, $querySize, $wordInTocNum)
	{
		return $ratio * self::repeatWeightRatio($wordInTocNum);
	}

	/**
	 * @param ResultSet $resultSet
	 */
	public function fillResultSet(ResultSet $resultSet)
	{
		$queryWordCount = $this->query->getCount();

		foreach ($this->fulltextIndexContent->toArray() as $word => $items) {
			$reductionRatio = self::frequencyReduction($this->tocSize, count($items));

			foreach ($items as $externalId => $positions) {
				$weight = self::fulltextWeight($reductionRatio, $queryWordCount, count($positions));
				$resultSet->addWordWeight($word, $externalId, $weight, $positions);
			}
		}
	}
}
