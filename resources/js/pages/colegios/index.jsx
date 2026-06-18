import { Head, usePage } from '@inertiajs/react'
import { ColegioTable } from '../colegios/components/colegio-table'


export default function Index() {
    const { colegios } = usePage().props

    return (
        <>
            <Head title="Colégios" />
            <div className="mx-auto w-full max-w-5xl px-4 py-6">
                <ColegioTable colegios={colegios ?? []} />
            </div>
        </>
    )
}