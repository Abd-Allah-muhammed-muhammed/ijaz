import {AuthLayout} from "@/apps/admin/layouts/AuthLayout";
import {Login} from "@/apps/admin/pages/Auth/components/Login";
import {Head} from "@inertiajs/react";

export default function () {
    return (
        <AuthLayout>
            <Head title={'Login'}/>
            <Login/>
        </AuthLayout>
    )
}
