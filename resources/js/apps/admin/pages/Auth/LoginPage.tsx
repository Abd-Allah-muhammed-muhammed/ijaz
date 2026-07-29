import {AuthLayout} from "@/app/modules/auth/AuthLayout";
import {Login} from "@/app/modules/auth/components/Login";
import {Head} from "@inertiajs/react";

export default function () {
    return (
        <AuthLayout>
            <Head title={'Login'}/>
            <Login/>
        </AuthLayout>
    )
}
