enum SellingStage: string
{
    case INTRO = 'introduction';
    case DISCOVERY = 'discovery';
    case EDUCATION = 'education';
    case QUALIFY = 'qualify';
    case QUOTE = 'quote';
    case CLOSE = 'close';

    public static function ordered(): array
    {
        return [
            self::INTRO,
            self::DISCOVERY,
            self::EDUCATION,
            self::QUALIFY,
            self::QUOTE,
            self::CLOSE,
        ];
    }

    public function next(): ?self
    {
        $ordered = self::ordered();
        $i = array_search($this, $ordered, true);

        return $ordered[$i + 1] ?? null;
    }
}
