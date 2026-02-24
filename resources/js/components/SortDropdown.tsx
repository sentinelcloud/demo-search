import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface SortDropdownProps {
    value: string;
    onChange: (value: string) => void;
}

export function SortDropdown({ value, onChange }: SortDropdownProps) {
    return (
        <Select value={value || 'relevance'} onValueChange={onChange}>
            <SelectTrigger className="w-[160px] h-9">
                <SelectValue placeholder="Sort by" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="relevance">Relevance</SelectItem>
                <SelectItem value="price_asc">Price: Low → High</SelectItem>
                <SelectItem value="price_desc">Price: High → Low</SelectItem>
                <SelectItem value="rating">Top Rated</SelectItem>
                <SelectItem value="newest">Newest</SelectItem>
            </SelectContent>
        </Select>
    );
}
