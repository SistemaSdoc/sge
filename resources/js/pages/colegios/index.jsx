import { Head, usePage } from '@inertiajs/react'
import { ColegioTable } from '../colegios/components/colegio-table'


export default function Index() {
    const { colegios } = usePage().props

    return (
        <>
            <Head title="Colégios" />
            <div className="w-full max-w-7xl mx-auto">
                <ColegioTable colegios={colegios ?? []} />
            </div>
        </>
    )
}