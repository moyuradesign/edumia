<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CartController extends Controller
{
    private const SESSION_KEY = 'cart_course_ids';
    private const MAX_COURSES = 20;

    public function index(Request $request): View
    {
        $ids = self::validIds($request);
        $courses = Course::published()
            ->whereIn('id', $ids)
            ->with('category')
            ->get()
            ->sortBy(static fn (Course $course) => array_search((int) $course->getKey(), $ids, true))
            ->values();

        $requestedLocale = $request->query('locale');
        $locale = Localization::resolve(is_string($requestedLocale) ? $requestedLocale : null);

        return view('frontend.default.cart.index', [
            'locale' => $locale,
            'languages' => Localization::LANGUAGES,
            'courses' => $courses,
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless(
            $course->status === 'published'
            && $course->visibility === 'public'
            && $course->published_at !== null
            && $course->published_at->lte(now()),
            404
        );

        $ids = self::validIds($request);
        $courseId = (int) $course->getKey();

        if (! in_array($courseId, $ids, true)) {
            if (count($ids) >= self::MAX_COURSES) {
                return back()->with('error', 'Your cart can hold up to 20 courses.');
            }

            $ids[] = $courseId;
            $request->session()->put(self::SESSION_KEY, $ids);
        }

        return to_route('cart.index')->with('success', 'Course added to your cart.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $ids = self::validIds($request);
        $request->session()->put(self::SESSION_KEY,
            array_values(array_filter($ids, static fn (int $id): bool => $id !== (int) $course->getKey())));

        return to_route('cart.index')->with('success', 'Course removed from your cart.');
    }

    /**
     * Strictly accept bounded unsigned integer identifiers from this session.
     * Never trust arbitrary session payloads for user-visible catalog items.
     *
     * @return list<int>
     */
    private static function validIds(Request $request): array
    {
        $raw = $request->session()->get(self::SESSION_KEY, []);
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (! is_int($value) || $value < 1 || in_array($value, $ids, true)) {
                continue;
            }
            $ids[] = $value;
            if (count($ids) >= self::MAX_COURSES) {
                break;
            }
        }

        return $ids;
    }
}
